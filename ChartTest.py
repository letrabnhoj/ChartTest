##  Author: John Bartel
##  Last Mod: Feb-14 2019

import binascii #For decoding the blob.
import struct #For converting hex string to signed int.
import time   #For setting interval of chart calls.

##External dependencies
import mysql.connector  #For Connecting to DB.
import matplotlib.pyplot as plt   #For plotting the points.
import matplotlib.animation as animation  #Setting a interval to change plot.

#Database connections settings
mydb = mysql.connector.connect(
  host="localhost",
  user="root",
  passwd="",
  database="gps"
)
sql = "SELECT trace_id, trace_data, trace_time FROM test ORDER BY trace_id"
tableCall = mydb.cursor() #Connection var.
tableCall.execute(sql)
result = tableCall.fetchall() #Var to hold database query result.

figure = plt.figure() #Create a figure for plots.
chart = figure.add_subplot(1,1,1) #Adds generic subplot that can be cleared and changed.
counter = 0 

def createGraph(i):
    global counter
    index = counter%len(result)
    counter += 1  #counter for getting values from query. Mod by length
    temp = binascii.hexlify(bytes(result[index][1].decode('utf-8'), 'utf-16-BE')).decode('utf-8') #Converts the block to a hex string. 
    point = [[],[]]   #coordinate array.
    xPos = 850    #Starting x pox so it can start at 850 MHZ.

    #Slices string into 16 piece strings because during conversion each bin char was converted to 8-bit hex.
    for i in range(0, len(temp)+1, 16):
        tempHex = ""
        tempSub = temp[(i-16):i]

        #Takes last 2 chars of string slices and combines.
        for j in range(0, len(tempSub)+1, 4):
            tempHex += tempSub[(j-4):j][2:4]
        #Have to check for null chars.
        if tempHex == '':
            continue
        else: #Converts hex string to signed int and divide by 1k.
            yPos = struct.unpack('>i', bytes.fromhex(tempHex))[0]/1000
            #Appends coordinates to array.
            point[0].append(xPos)
            point[1].append(yPos)
            xPos+=0.5

    chart.clear()   #Clear plot between iterations.
    chart.plot(point[0], point[1], color = "yellow", linewidth = 0.7) #plots the graph.
    chart.axis([850, 1150, -60, -30])
    chart.set_xlabel(result[index][2])    #Date stamp along xaxis.
    chart.set_yticks([-60, -50, -40, -30])
    chart.set_yticklabels(['-60 dBm', '-50 dBm','-40 dBm','-30 dBm'])
    chart.set_xticks([850,1000,1150])
    chart.set_xticklabels(['850 MHZ','1000 MHZ','1150 MHZ'])
    chart.set_title("Trace ID: "+str(result[index][0]))   #trace_id along y.
    chart.grid(linestyle='--', linewidth='0.4', color='grey') #inner grid marks.
    chart.set_facecolor('black')
#Sets the animation interval for 1 second and calls createGraph.
graphStep = animation.FuncAnimation(figure, createGraph, interval=1000)
plt.show()
