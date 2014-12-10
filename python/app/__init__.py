from flask import Flask
from flask.ext.sqlalchemy import SQLAlchemy
from sqlalchemy.ext.declarative import declarative_base

app = Flask(__name__)
app.config.from_object('config')
#app.config['SQLALCHEMY_DATABASE_URI'] = SQLALCHEMY_DATABASE_URI 
db = SQLAlchemy(app)
engine=db.engine
db.Model.metadata.reflect(db.engine)

#for testing
lorenzo={'id':'1317291089', 'name': 'Lorenzo Bossi'}
xiao = {'id':'1229316162', 'name': 'Qian Xiao'}
claudio = {'id': '1320921217', 'name': 'Claudio Pinto'}
 

from app import views, models



