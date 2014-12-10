import sys
sys.path.append("/Users/xiaoqian/Dropbox/research/Web_codes/Workspace/cac_python")

from sqlalchemy import tuple_
from app import app
from app import db
from app import views, modelsc          
import itertools
from app import cac
import timeit


Users = views.Users
Iscore = views.Iscore
PEscore = views.PEscore
Results = models.Results
uid = '1'
conn = db.engine.connect()

#delete
# user = db.session.query(views.Users).filter(views.Users.id=='1').first()
# db.session.delete(user)
# db.session.commit()
#delete all
#views.Users.query.delete()

flag_add_synthetic_data = True
num_of_users = 10
num_of_photos = 100
ids = [str(i) for i in range(1,num_of_users+1)]
names = ['u'+id for id in ids]
pids = ['p'+str(i) for i in range(1, num_of_photos+1)]
if flag_add_synthetic_data:
	print "start resetting the database"
	#clear all table content
	Users.query.delete()
	Iscore.query.delete()
	PEscore.query.delete()
	Results.query.delete()
	print "begin"
	# users = zip(ids, names)
	# raw_sql_insert = "INSERT INTO users (id, name) VALUES "+ str(users)[1:-1]
	# conn.execute(raw_sql_insert)
	# print "finish inserting users"
	
	# iscores = []
	# for pid in pids:
	# 	for userid in ids:
	# 		iscores.append((str(userid), str(pid), 5, 5, 5, 5))	
	# raw_sql_insert = "INSERT INTO iscore (userid, photo, private, friends, fof, everyone) VALUES "+str(iscores)[1:-1]
	# # conn.execute(raw_sql_insert)
	# print "finish inserting iscores"

	# pescores = []
	# for r in itertools.permutations(ids, 2):
	# 	pescores.append(r[0], r[1], 0)
	# raw_sql_insert = "INSERT INTO PEscore (user1, user2, score) VALUES "+str(pescores)[1:-1]
	# conn.execute(raw_sql_insert)
	# print "finish inserting iscores"
	
	for i in range(len(ids)):
		u = Users(ids[i], names[i])
		db.session.add(u)
	for pid in pids:
		for userid in ids:
			temp = Iscore(userid, pid)
			db.session.add(temp)

	for r in itertools.permutations(ids, 2):
		temp = PEscore(r[0], r[1])
		db.session.add(temp)
	db.session.commit()
	print "finish resetting database synthetic data."

start = timeit.default_timer()
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
	coordinator = cac.CAC(curr_iscores, curr_pescores)
	decisions = coordinator.reach_equilibrium()
	photo_decision_dict[pid] = decisions
	for userid in users:
		filter_args.append((userid, pid))
Results.query.filter(tuple_(Results.userid, Results.photoid).in_(filter_args)).delete(synchronize_session=False)
db.session.commit()
print "writing the decisions"
decision_list = [(str(userid), str(photoid), photo_decision_dict[photoid][userid]) for userid, photoid in filter_args]
raw_sql_insert = "INSERT INTO Results (userid, photoid, decision) VALUES "+str(decision_list)[1:-1]
conn.execute(raw_sql_insert)
stop = timeit.default_timer()
print "running time: ", stop-start
