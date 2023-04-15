import tensorflow as tf
from tensorflow import keras
from tensorflow.keras.preprocessing.image import load_img, img_to_array
import numpy as np

# Load the pre-trained image classification model
model = keras.models.load_model('animal_classifier_model.h5')

# Load the image to be classified
img = load_img('dog.jpg', target_size=(224, 224))

# Convert the image to a numpy array
img_array = img_to_array(img)

# Add an extra dimension to the array to match the input shape of the model
img_array = np.expand_dims(img_array, axis=0)

# Preprocess the image array to match the format expected by the model
img_array = keras.applications.mobilenet_v2.preprocess_input(img_array)

# Use the model to classify the image
predictions = model.predict(img_array)

# Get the index of the category with the highest probability
predicted_index = np.argmax(predictions)

# Define a list of animal categories the model can classify
animal_categories = ['cat', 'dog', 'horse', 'bird', 'fish']

# Print the predicted animal category
if predicted_index < len(animal_categories):
    predicted_animal = animal_categories[predicted_index]
    print(f"The image contains a {predicted_animal}")
else:
    print("The image does not contain an animal")
