import os
basedir = os.path.abspath(os.path.dirname(__file__))

# db_choice = "lit-beach-4706"
db_choice = "collape"

if db_choice == "collape":
	user = 'wqjuclpagbdpyf'
	hostname = 'ec2-184-73-194-196.compute-1.amazonaws.com'
	dbname = 'd4agdaqc1h97l5'
	password = 'FMht_CqliNs6MTV22QHUmVjOgS'
	port = '5432'
else:
	hostname = "ec2-107-20-191-205.compute-1.amazonaws.com"
	dbname = "d3utvb1ohkfctp"
	user = "bwznjajuhpxcjk"
	port = '5432'
	password = "HEri1BNlnOLjX_4QZP1aI2NIbS"

SQLALCHEMY_DATABASE_URI = 'postgresql://' + user + ':' + password + '@' + hostname + ':' + port + '/' +dbname
SQLALCHEMY_MIGRATE_REPO = os.path.join(basedir, 'db_repository')
