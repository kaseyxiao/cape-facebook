import numpy as np

class CAC(object):
	"""docstring for CAC"""
	def __init__(self, iscores, pescores):
		super(CAC, self).__init__()
		self.option_mapping = {0:'private', 1:'friends', 2: 'fof', 3: 'everyone'}
		self.users = []
		num_of_users = len(iscores)
		self.iscores = np.zeros((num_of_users, len(self.option_mapping.keys())))
		self.pescores = np.zeros((num_of_users, num_of_users))
		for iscore in iscores:
			iscore = iscore.__dict__
			uid = iscore['userid']
			self.users.append(uid)
			idx = self.users.index(uid)
			for key, value in self.option_mapping.iteritems():
				self.iscores[idx][key] = iscore[value]
		for pescore in pescores:
			idx1 = self.users.index(pescore[0])
			idx2 = self.users.index(pescore[1])
			self.pescores[idx1][idx2] = pescore[2]

		


	def reach_equilibrium_testing(self):
		n = 5
		W = np.zeros((n,n))
		I = np.eye(n)
		alpha = np.array([0,5,5,5,5] ,dtype=float).reshape(n,1)
		beta = np.array([3,3,3,3,3], dtype=float).reshape(n,1)
		iscores_init = np.concatenate((alpha, beta), axis=1)
		iscores_equil = (np.matrix(I-W)).I * iscores_init
		max_score_index = np.argmax(np.asarray(iscores_equil), axis=1)
		decisions = {}
		for id, max_index in enumerate(max_score_index):
			decisions[self.users[id]] = self.option_mapping[max_index]
		return decisions

	def reach_equilibrium(self):
		n = len(self.users)
		W = self.pescores
		I = np.eye(n)
		iscores_init = self.iscores
		iscores_equil = (np.matrix(I-W)).I * iscores_init
		max_score_index = np.argmax(np.asarray(iscores_equil), axis=1)
		decisions = {}
		for id, max_index in enumerate(max_score_index):
			decisions[self.users[id]] = self.option_mapping[max_index]
		return decisions

if __name__ == '__main__':
	print "cac.py testing"	
		
