##	Author: John Bartel
## 	Last Mod: Feb-14 2019

import binascii	#For decoding the blob.
import struct	#For converting hex string to signed int.

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

index = 0
temp = binascii.hexlify(bytes(result[index][1].decode('utf-8'), 'utf-16-BE')).decode('utf-8') #Converts the block to a hex string. 

#Slices string into 16 piece strings because during conversion each bin char was converted to 8-bit hex.
for i in range(0, len(temp)+1, 16):
    tempHex = ''
    tempSub = temp[(i-16):i]

    #Takes last 2 chars of string slices and combines.
    for j in range(0, len(tempSub)+1, 4):
        tempHex += tempSub[(j-4):j][2:4]
    #Have to check for null chars.
    if tempHex == '':
        continue
    else:	#Converts hex string to signed int and divide by 1k.
        yPos = struct.unpack('>i', bytes.fromhex(tempHex))[0]/1000
        print(yPos)
