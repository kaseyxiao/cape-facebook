from app import db

class Users(db.Model):
	__table__ = db.Model.metadata.tables['users']

	def __init__(self, id, name):
		self.id = id
		self.name = name

	def load_user_by_id(id):
		return Users.query.get(id)

	# def __repr__(self):
	# 	return '<Users %r>'%(self.body)

class Iscore(db.Model):
	__table__ = db.Model.metadata.tables['iscore']
	__mapper_args__ = {
				'primary_key': [db.Model.metadata.tables['iscore'].c.userid, 
								db.Model.metadata.tables['iscore'].c.photo]
				}

	def __init__(self, uid, pid, private=5, friends=5, fof=5, public=5):
		self.userid = uid
		self.photo = pid
		self.private =private
		self.friends = friends
		self.fof = fof
		self.everyone = public

class PEscore(db.Model):
	__table__ = db.Model.metadata.tables['pescore']
	__mapper_args__ = {
			'primary_key': [db.Model.metadata.tables['pescore'].c.user1, 
							db.Model.metadata.tables['pescore'].c.user2]
			}
	def __init__(self, user1, user2, score=0):
		self.user1 = user1
		self.user2 = user2
		self.score = score

class Results(db.Model):
	__table__ = db.Model.metadata.tables['results']
	__mapper_args__ = {
		'primary_key': [db.Model.metadata.tables['results'].c.userid,
						db.Model.metadata.tables['results'].c.photoid]
		}

	def __init__(self, userid, photoid, decision):
		self.userid = userid
		self.photoid = photoid
		self.decision = decision