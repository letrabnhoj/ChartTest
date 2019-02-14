##	Author: John Bartel
## 	Last Mod: Feb-14 2019

##External dependencies
import mysql.connector	#For Connecting to DB.


#Database connections settings
mydb = mysql.connector.connect(
  host="localhost",
  user="root",
  passwd="",
  database="gps"
)
sql = "SELECT trace_id, trace_data, trace_time FROM test ORDER BY trace_id"
tableCall = mydb.cursor()	#Connection var.
tableCall.execute(sql)
result = tableCall.fetchall()	#Var to hold database query result.
print(result)
