from flask import render_template, request
from app import app, db
from sqlalchemy import *
from flask import jsonify, abort
from models import Users, Iscore, PEscore, Results
from app import lorenzo, xiao, claudio
from cac import CAC
import timeit
import itertools


tasks = [
    {
        'id': 1,
        'title': u'Buy groceries',
        'description': u'Milk, Cheese, Pizza, Fruit, Tylenol', 
        'done': False
    },
    {
        'id': 2,
        'title': u'Learn Python',
        'description': u'Need to find a good Python tutorial on the web', 
        'done': False
    }
]

@app.route('/')
@app.route('/index')
def index():
    user = { 'nickname': '' } # fake user
    return render_template("index.html",
        title = 'Home',
        user = user)




@app.route('/todo/api/v1.0/tasks', methods = ['GET'])
def get_tasks():
    uid = request.args['uid']
    return jsonify( { 'uid': uid } )



@app.route('/todo/api/v1.0/tasks/<int:task_id>', methods = ['GET'])
def get_task(task_id):
    task = filter(lambda t: t['id'] == task_id, tasks)
    if len(task) == 0:
        abort(404)
    return jsonify( { 'task': task[0] } )

@app.route('/api/v1.1/cac', methods = ['GET'])
def create_cac_task_2():
    if 'uid' not in request.args:
        abourt(404)
    conn = db.engine.connect()
    start = timeit.default_timer()
    uid = request.args['uid']
    rows = Iscore.query.filter_by(userid = uid).all()
    pids = [row.photo for row in rows]
    iscore_rows = Iscore.query.filter(Iscore.photo.in_(pids)).order_by(Iscore.photo).all()
    photo_user_dict = {}
    photo_iscore_dict = {}
    for pid in pids:
        photo_user_dict[pid] = [] 
        photo_iscore_dict[pid] = []
    for row in iscore_rows:
        photo_user_dict[row.photo].append(row.userid)
        photo_iscore_dict[row.photo].append(row)

    pescore_pairs = {}
    filter_args = []
    for value in photo_user_dict.itervalues():
        user_pairs = list(itertools.permutations(value, 2))
        for pair in user_pairs:
            if pair not in filter_args:
                filter_args.append(pair)
    rows = PEscore.query.filter(tuple_(PEscore.user1, PEscore.user2).in_(filter_args)).all()
    for row in rows:
        if (row.user1, row.user2) not in pescore_pairs:
            pescore_pairs[(row.user1, row.user2)] = row.score
    photo_decision_dict = {}
    filter_args = []
    for pid in pids:
        users = photo_user_dict[pid]
        curr_iscores = photo_iscore_dict[pid]
        curr_pescores = []
        for pair in list(itertools.permutations(users,2)):
            curr_pescores.append([pair[0], pair[1], pescore_pairs[pair]])
        coordinator = CAC(curr_iscores, curr_pescores)
        decisions = coordinator.reach_equilibrium()
        photo_decision_dict[pid] = decisions
        for userid in users:
            filter_args.append((userid, pid))
    Results.query.filter(tuple_(Results.userid, Results.photoid).in_(filter_args)).delete(synchronize_session=False)
    db.session.commit()
    # print "writing the decisions"
    decision_list = [(str(userid), str(photoid), photo_decision_dict[photoid][userid]) for userid, photoid in filter_args]
    raw_sql_insert = "INSERT INTO Results (userid, photoid, decision) VALUES "+str(decision_list)[1:-1]
    conn.execute(raw_sql_insert)
    stop = timeit.default_timer()
    # print "running time: ", stop-start
    return jsonify( { 'uid': uid, 'status': 'success', 'running time': stop-start} ), 201


@app.route('/api/v1.0/cac', methods = ['POST'])
def create_cac_task():
    if not request.json or not 'uid' in request.json:
        abort(400)
    start = timeit.default_timer()
    uid = request.json['uid']
    rows = Iscore.query.filter_by(userid = uid).all()
    pids = [row.photo for row in rows]

    for pid in pids:
        iscores = Iscore.query.filter_by(photo = pid).all()
        participants = [iscore.userid for iscore in iscores]
        pescores =[]
        for fid in participants:
            if fid != uid:
                record1 = PEscore.query.filter_by(user1 = uid, user2 = fid).first()
                record2 = PEscore.query.filter_by(user1 = fid, user2 = uid).first()
                pescores.append([uid, fid, record1.score])
                pescores.append([fid, uid, record2.score])
        coordinator = CAC(iscores, pescores)
        decisions = coordinator.reach_equilibrium()
        for id in participants:
            record = Results.query.filter_by(userid=id, photoid=pid).first()
            if record:
                record.decision = decisions[id]
            else:
                temp = models.Results(id, pid, decisions[id])
                db.session.add(temp)
        print decisions
    db.session.commit()
    stop = timeit.default_timer()
    return jsonify( { 'uid': uid, 'status': 'success', 'running time': stop-start} ), 201


@app.route('/todo/api/v1.0/tasks', methods = ['POST'])
def create_task():
    if not request.json or not 'uid' in request.json:
        abort(400)
    uid = request.json['uid']
    # pid = request.json['pid']

    results = Iscore.query.filter_by(userid = uid).all()
    pids = [result.photo for result in results]

    for pid in pids:
        iscores = Iscore.query.filter_by(photo = pid).all()

    participants = [iscore.userid for iscore in iscores]
    pescores =[]
    for fid in participants:
        score_1 = PEscore.query.filter_by(user1 = uid, user2 = fid).first()
        score_2 = PEscore.query.filter_by(user1 = fid, user2 = uid).first()
        pescores.append([uid, fid, score_1])
        pescores.append([fid, uid, score_2])

    coordinator = CAC(None, None)
    decisions = coordinator.reach_equilibrium()


    return jsonify( { 'pid': pid, 'decisions': decisions} ), 201

if __name__ == '__main__':
    print "view.py"


