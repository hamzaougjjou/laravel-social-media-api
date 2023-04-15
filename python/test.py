# import cv2
from info import storageUrl
from info import animalsLis
from time import sleep 
import mysql.connector

import tensorflow as tf
import numpy as np
from tensorflow.keras.preprocessing import image
from tensorflow.keras.applications import imagenet_utils


def process_img(filename):
    img = image.load_img(filename )
    # //mobilenet_v2
    mobile = tf.keras.applications.mobilenet.MobileNet()
    # mobile = tf.keras.applications.mobilenet_v2.MobileNetV2()
    img = image.load_img(filename , target_size=(224,224) )
    resized_img = image.img_to_array(img)
    finale_img = np.expand_dims( resized_img , axis=0)
    finale_img = tf.keras.applications.mobilenet.preprocess_input(finale_img)
    # finale_img.shape 
    predictions = mobile.predict(finale_img)
    results = imagenet_utils.decode_predictions(predictions)
    # print(results)
    for lst in results:
        return lst


mainPath = "../public/"
while( True ):
    db = mysql.connector.connect(
        host="localhost",
        user="hamza",
        password="hamza",
        database="pets_db"
    )
    mycursor = db.cursor()
    mycursor.execute("""SELECT
                            files_references.file_id,
                            files.file
                        FROM
                            files_references
                            INNER JOIN files ON (files_references.file_id = files.id)
                        WHERE
                            files_references.type = 'cover' OR files_references.type = 'profile'
                        order by files_references.created_at DESC
                    """)
    myresult = mycursor.fetchall()
    print("data length = " , len(myresult) )
    for dbItem in myresult:
        filename = mainPath + dbItem[1]
        data = process_img(filename)
        print(data)
        # check if returned data contain animal names
        exist =False
        animal_type = 0
        for animal in animalsLis:
            for item in data:
                txt = item[1].lower()
                x = txt.rfind( animal.lower() )
                if( x !=-1 ):
                    animal_type = animal
                    exist = True
        if( exist ):
            print('this is a valid image = ' , animal_type)
            print('data = ' , dbItem )
        else:
            print('invalid image = ')
            print('data = ' , dbItem )
        print("++++++++++++++++++++++++++++")

    sleep(5)








# Display an image in a window
# cv2.imshow('Cat Image',img)
# cv2.waitKey(0)
# cv2.destroyAllWindows()
# from info import mainUrl
# from info import storageUrl
# from info import db
# from info import mainUrl
# from info import storageUrl
# mycursor = db.cursor()
# mycursor.execute("SELECT * FROM files_references where type='cover' or type='profile' ")
# myresult = mycursor.fetchall()
# for x in myresult:
#   print(x)