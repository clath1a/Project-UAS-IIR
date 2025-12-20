import sys
from nltk.tokenize import word_tokenize
from nltk.stem import PorterStemmer

# Inisialisasi stemmer (contoh: Porter)
ps = PorterStemmer() 

# Ambil kalimat dari argumen baris perintah
sentence = sys.argv[1] 

stemmed_sentence = ""
for word in word_tokenize(sentence):
    stemmed_sentence += " " + ps.stem(word)

print(stemmed_sentence.strip())