import requests
from datetime import datetime
from haversine import haversine, Unit
import time

api_key = '8923a80ca7164210b07f92c4f47268f1'
headers = {'api-key': api_key}

url = 'https://dataapi.paj.com.my/api/v1/bus-live/route/P101'
insertLocationURL = 'https://laravelsyd-fypfinalver.herokuapp.com/bus_insertlocation'
insertLocation_data = {'latlong':"1,2", 'speed':0, 'imei':"123345", 'date':'2024-01-01 00:00:00','route_id':16, 'bus_id':35}

removephantomETA = 'https://laravelsyd-fypfinalver.herokuapp.com/removephantomETA'
phantom_data = {'bus_id':35}

laravelETA = 'https://laravelsyd-fypfinalver.herokuapp.com/getETA'
laravelETA_headers = {'content-type' : 'application/json'}
laravelETA_data = {'bus_stop_id':1036, 'route_id':16, 'bus_id':35, 'service_no':'P101'}

location_data = {'latitude':0, 'longitude':0, 'speed':0, 'bus':"JSN0000", 'route':16, 'bus_id':0, 'time':"2024-01-01 00:00:00"}
#location_data = {'latitude':0, 'longitude':0, 'speed':0, 'fuel':0, 'status': "On route"}
p101 = {
    "JSN7055": location_data,
    "JSN5814": location_data,
    "JSN4410": location_data,
}

bus_terminal ={
    "Larkin": (1.495144,103.742662),
    "Kulai": (1.662585,103.598608),
    "JB": (1.463400,103.764932),
    "TamanUniversiti": (1.538506,103.628711),
}

p101_bus = {
    "JSN7055": 35,
    "JSN5814": 36,
    "JSN4410": 37,
}

#while(1):
response = requests.get(url, headers=headers)
fleet = response.json()

for i in range (len(fleet["data"])):
    bus = fleet["data"][i]

    info = p101[bus["bus"]]
    info["latitude"] = bus["latitude"]
    info["longitude"] = bus["longitude"]
    info["speed"] = bus["speed"]
    info["time"] = bus["timestamp"]
    info["bus_id"] = p101_bus[bus['bus']]
    currentLocation = (bus["latitude"], bus["longitude"])
    print(currentLocation)

    curr_timestamp = datetime.now()
    prev_timestamp = datetime.strptime(info["time"], "%Y-%m-%d %H:%M:%S")
    print(curr_timestamp.timestamp() - prev_timestamp.timestamp())
    #only update the location if
    if (curr_timestamp.timestamp() - prev_timestamp.timestamp() < 300):
        if ((bus['route'] == ['P101']) and (bus["speed"] != 0)):
            #parameters for insert location
            insertLocation_data['speed'] = info['speed']
            insertLocation_data['date'] = info['time']
            insertLocation_data['latlong'] = str(info['latitude'])+"," + str(info['longitude'])
            insertLocation_data['bus_id'] = info['bus_id']

            #determine the route of the bus (Larkin to JB)
            laravelETA_data["route_id"] = 16
            laravelETA_data["service_no"] = "P101"
            laravelETA_data["bus_id"] = p101_bus[bus['bus']]
            laravelETA_data["bus_stop_id"] = 1036

            route = requests.post(laravelETA, params=laravelETA_data, headers=laravelETA_headers)
            print(route.status_code)
            print(route.text)
            #there is no bus arrival time, the bus could be departing from Larkin, or it is on route 17
            if (route.status_code == 400):
                #determine the route of the bus (JB to Larkin)
                laravelETA_data["route_id"] = 17
                laravelETA_data["service_no"] = "P101"
                laravelETA_data["bus_id"] = p101_bus[bus['bus']]
                laravelETA_data["bus_stop_id"] = 1076
                eta = requests.post(laravelETA, params=laravelETA_data, headers=laravelETA_headers)
                print("check route 17")
                print(eta.status_code)
                print(eta.text)
                if (eta.status_code == 400):
                    #check whether the location is close to Larkin or JB Sentral. If Larkin Sentral, then insert as route 16, else insert as route 17.

                    distance = haversine(bus_terminal["JB"], currentLocation)
                    print("Distance to JB")
                    print(distance)
                    insertLocation_data['route_id'] = 16
                    insert = requests.post(insertLocationURL, params=insertLocation_data, headers=laravelETA_headers)
                    print("insert route 16")
                    print(insert.status_code)
                    print(insert.text)

                    distance = haversine(bus_terminal["JB"], currentLocation)
                    print("Distance to JB Sentral")
                    print(distance)
                    if (insert.text == "Too far from route"):
                        #     #close to JB Sentral, insert data as route 17
                        insertLocation_data['route_id'] = 17
                        insert = requests.post(insertLocationURL, params=insertLocation_data, headers=laravelETA_headers)
                        print("insert to JB route 17")
                        print(insert.status_code)
                        print(insert.text)
                elif (eta.status_code == 200):
                    #insert as route 17
                    insertLocation_data['route_id'] = 17
                    insert = requests.post(insertLocationURL, params=insertLocation_data, headers=laravelETA_headers)
                    print("insert to JB route 17")
                    print(insert.status_code)
                    print(insert.text)
            #there is a bus arrival time, meaning that the bus is en route from Larkin to JB Sentral
            elif (route.status_code == 200):

                #if the bus is approaching JB Sentral, then insert as route 17.
                currentLocation = (bus["latitude"], bus["longitude"])
                distance = haversine(bus_terminal["JB"], currentLocation)
                print("Distance to JB Sentral")
                print(distance)
                if (distance < 0.25):
                    #close to JB Sentral, insert data as route 17
                    insertLocation_data['route_id'] = 17
                    insert = requests.post(insertLocationURL, params=insertLocation_data, headers=laravelETA_headers)
                    print("insert to JB route 17")
                    print(insert.status_code)
                    print(insert.text)
                #if the bus is inside larkin terminal? then remove ETA.
                distance = haversine(bus_terminal["Larkin"], currentLocation)
                print("Distance to Larkin")
                print(distance)
                #if the bus is still in Larkin, remove ETA.
                if ((distance < 0.15) and (info['speed']==0)):
                    phantom_data['bus_id'] = info["bus_id"]
                    phantom = requests.post(removephantomETA, params=phantom_data, headers=laravelETA_headers)
                    print("removePhantomETA")
                else:
                    #insert as route 16
                    insertLocation_data['route_id'] = 16
                    insert = requests.post(insertLocationURL, params=insertLocation_data, headers=laravelETA_headers)
                    print("insert to Larkin route 16")
                    print(insert.status_code)
                # p101[bus["bus"]] = info
        elif ((bus['route'] == ['P101']) and (bus["speed"] == 0)):
            distance = haversine(bus_terminal["Larkin"], currentLocation)
            print("Distance to Larkin")
            print(distance)
            if (distance < 0.1):
                phantom_data['bus_id'] = info["bus_id"]
                phantom = requests.post(removephantomETA, params=phantom_data, headers=laravelETA_headers)
                print("removePhantomETA")

            currentLocation = (bus["latitude"], bus["longitude"])
            distance = haversine(bus_terminal["JB"], currentLocation)
            print("Distance to JB Sentral")
            print(distance)
            if (distance < 0.25):
                #close to JB Sentral, insert data as route 17
                insertLocation_data['route_id'] = 17
                insert = requests.post(insertLocationURL, params=insertLocation_data, headers=laravelETA_headers)
                print("insert to JB route 17")
                print(insert.status_code)
                print(insert.text)

if response.status_code == 200:
    print(response.json())
else:
    print('Error:', response.status_code)

    #time.sleep(60)
