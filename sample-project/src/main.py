# -*- coding:utf-8 -*-
import mysql.connector
import redis
from http.server import HTTPServer, BaseHTTPRequestHandler
from urllib.parse import urlparse, parse_qs


class HTTPRequestHandler(BaseHTTPRequestHandler):
    def return_response(self, status_code, message):
        self.send_response(status_code)
        self.send_header('Content-Type', 'text/html; charset=utf-8')
        self.end_headers()
        self.wfile.write(message.encode('utf-8'))

    def do_GET(self):
        parsed = urlparse(self.path)

        if parsed.path == '/hello':
            self.return_response(200, f'Hello world</h1>')
        elif parsed.path == '/mysql-version':
            mysql_db = mysql.connector.connect(
                host="hostname-mysql",
                user="root",
                passwd="root",
            )
            cursor = mysql_db.cursor()
            cursor.execute("SELECT VERSION()")
            mysql_version = next(cursor)[0]

            self.return_response(200, f'<h1>MySQL version: {mysql_version}</h1>')
        elif parsed.path == '/redis-version':
            r = redis.Redis(host='hostname-redis', port=6379, db=0)
            redis_version = r.info()['redis_version']

            self.return_response(200, f'<h1>Redis version: {redis_version}</h1>')
        else:
            self.return_response(404, '<h1>처리할 수 없는 요청입니다.</h1>')

    def do_POST(self):
        pass


def run(server_class=HTTPServer, handler_class=HTTPRequestHandler, port=8080):
    """Entrypoint for python server"""
    server_address = ("0.0.0.0", port)
    httpd = server_class(server_address, handler_class)
    print(f"The server is listening http://localhost:{port}")
    httpd.serve_forever()


if __name__ == "__main__":
    run()
