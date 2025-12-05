# test_db.py
import mysql.connector

conn = mysql.connector.connect(
    host="localhost",
    port=3307,
    user="root",
    password="12345",
    database="sgtm_puce"
)

cursor = conn.cursor()
cursor.execute("SELECT COUNT(*) FROM usuarios")
total_usuarios = cursor.fetchone()[0]

print("Total de usuarios:", total_usuarios)

cursor.close()
conn.close()
