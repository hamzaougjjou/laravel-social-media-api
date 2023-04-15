

txt = "it is a lange text"
x = txt.rfind("text")

print(x)


# import tensorflow as tf
# import numpy as np

# from tensorflow.keras.preprocessing import image
# from tensorflow.keras.applications import imagenet_utils

# # from tensorflow.keras.preprocessing.image import load_img, img_to_array

# # from tensorflow import keras
# # from tensorflow.keras.preprocessing.image import load_img, img_to_array

# filename = 'cat2.jpg'
# img = image.load_img(filename  )

# # //mobilenet_v2
# # mobile = tf.keras.applications.mobilenet.MobileNet()
# mobile = tf.keras.applications.mobilenet_v2.MobileNetV2()
# img = image.load_img(filename , target_size=(224,224) )

# resized_img = image.img_to_array(img)
# finale_img = np.expand_dims( resized_img , axis=0)
# finale_img = tf.keras.applications.mobilenet.preprocess_input(finale_img)
# finale_img.shape 
# predictions = mobile.predict(finale_img)
# results = imagenet_utils.decode_predictions(predictions)

# print(results)
# for lst in results:
#     for item in lst:
#         print(item)
























# img_url = mainPath + item[1]
# # ===============================================
# filename = 'cat2.jpg'
# img = image.load_img(filename  )

# # //mobilenet_v2
# # mobile = tf.keras.applications.mobilenet.MobileNet()
# mobile = tf.keras.applications.mobilenet_v2.MobileNetV2()
# img = image.load_img(filename , target_size=(224,224) )

# resized_img = image.img_to_array(img)
# finale_img = np.expand_dims( resized_img , axis=0)
# finale_img = tf.keras.applications.mobilenet.preprocess_input(finale_img)
# finale_img.shape 
# predictions = mobile.predict(finale_img)
# results = imagenet_utils.decode_predictions(predictions)

# print(results)
# for lst in results:
#     for item in lst:
#         print(item)
# # ===============================================
# img = cv2.imread(img_url)

# # convert the input image to grayscale
# gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)

# # read the haarcascade to detect cat faces
# cat_cascade = cv2.CascadeClassifier('cat.xml')

# # Detects cat faces in the input image
# faces = cat_cascade.detectMultiScale(gray, 1.1, 3)
# print('Number of detected cat faces:', len(faces))

# # if atleast one cat face id detected
# if len(faces) > 0:
#     # print("Cat face detected")
#     for (x,y,w,h) in faces:
#         # To draw a rectangle in a face
#         cv2.rectangle(img,(x,y),(x+w,y+h),(0,255,255),2)
#         cv2.putText(img, 'cat face', (x, y-3),
#         cv2.FONT_HERSHEY_SIMPLEX, 0.5, (0,255,0), 1)