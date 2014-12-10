from sqlalchemy import *
from migrate import *


from migrate.changeset import schema
pre_meta = MetaData()
post_meta = MetaData()
iscore = Table('iscore', pre_meta,
    Column('userid', VARCHAR(length=20), nullable=False),
    Column('photo', VARCHAR(length=50), nullable=False),
    Column('private', INTEGER, nullable=False),
    Column('friends', INTEGER, nullable=False),
    Column('fof', INTEGER, nullable=False),
    Column('everyone', INTEGER, nullable=False),
)

pescore = Table('pescore', pre_meta,
    Column('user1', VARCHAR(length=20), nullable=False),
    Column('user2', VARCHAR(length=20), nullable=False),
    Column('score', INTEGER, nullable=False),
)

users = Table('users', pre_meta,
    Column('id', VARCHAR(length=20), primary_key=True, nullable=False),
    Column('name', VARCHAR(length=30), nullable=False),
)


def upgrade(migrate_engine):
    # Upgrade operations go here. Don't create your own engine; bind
    # migrate_engine to your metadata
    pre_meta.bind = migrate_engine
    post_meta.bind = migrate_engine
    pre_meta.tables['iscore'].drop()
    pre_meta.tables['pescore'].drop()
    pre_meta.tables['users'].drop()


def downgrade(migrate_engine):
    # Operations to reverse the above upgrade go here.
    pre_meta.bind = migrate_engine
    post_meta.bind = migrate_engine
    pre_meta.tables['iscore'].create()
    pre_meta.tables['pescore'].create()
    pre_meta.tables['users'].create()
