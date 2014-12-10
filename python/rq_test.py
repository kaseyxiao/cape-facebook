from myfunc import count_words_at_url
from redis import Redis
from rq import Connection, Queue
import time


# Connect to Redis
redis_conn = Redis()
q = Queue(connection=redis_conn)

# Offload the "myfunc" invocation
job = q.enqueue(count_words_at_url, 'http://www.google.com')
print job.result

time.sleep(5)
print job.result