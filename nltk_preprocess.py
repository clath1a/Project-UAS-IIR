# nltk_preprocess.py

import sys
import json
from nltk.tokenize import word_tokenize
from nltk.stem import PorterStemmer, WordNetLemmatizer
# Anda harus sudah menjalankan ini di terminal:
# import nltk; nltk.download('punkt'); nltk.download('wordnet');

def process_text(text):
    ps = PorterStemmer()
    lemmatizer = WordNetLemmatizer()
    
    # Tokenization
    words = word_tokenize(text)
    
    # Proses
    # Ingat: NLTK adalah untuk B. Inggris, jadi stem/lemma tidak optimal untuk B. Indonesia!
    porter_stemmed = ' '.join([ps.stem(word) for word in words])
    wordnet_lemmatized = ' '.join([lemmatizer.lemmatize(word) for word in words])
    
    # Kembalikan hasil dalam format JSON yang bersih
    return json.dumps({
        'porter_stemming': porter_stemmed,
        'wordnet_lemmatization': wordnet_lemmatized
    })

if __name__ == "__main__":
    if len(sys.argv) > 1:
        input_text = sys.argv[1]
        try:
            # Penting: Hanya mencetak string JSON
            print(process_text(input_text))
        except Exception as e:
            # Jika ada error runtime, cetak pesan error dalam format JSON
            print(json.dumps({'error_python': str(e)}))
    else:
        # Jika tidak ada input, cetak JSON error
        print(json.dumps({'error_python': 'No input provided'}))