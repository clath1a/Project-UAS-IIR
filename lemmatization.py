import sys
from nltk.tokenize import word_tokenize
from nltk.stem import WordNetLemmatizer

# Inisialisasi lemmatizer
lemmatizer = WordNetLemmatizer()

# Ambil kalimat dari argumen
sentence = sys.argv[1] 
sentence_lemma = ""

# WordNetLemmatizer di NLTK akan mengembalikan hasil yang berbeda 
# jika kita juga menyediakan Part-of-Speech (POS) dari setiap kata. 
# Tanpa POS, ia secara default menganggap kata tersebut adalah kata benda.
# Untuk kesederhanaan, kita abaikan POS di sini seperti di code Anda.

for word in word_tokenize(sentence):
    sentence_lemma += " " + lemmatizer.lemmatize(word)

print(sentence_lemma.strip())