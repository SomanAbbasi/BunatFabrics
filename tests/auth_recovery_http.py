"""Run after account_settings_http.py against the explicit isolated server."""
import email
import http.cookiejar
import pathlib
import re
import urllib.error
import urllib.parse
import urllib.request

BASE = 'http://127.0.0.1:8098'
class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *args, **kwargs):
        return None
class Client:
    def __init__(self):
        self.jar = http.cookiejar.CookieJar()
        self.opener = urllib.request.build_opener(NoRedirect(), urllib.request.HTTPCookieProcessor(self.jar))
    def request(self, path, data=None):
        try:
            response = self.opener.open(BASE + path, None if data is None else urllib.parse.urlencode(data).encode())
        except urllib.error.HTTPError as error:
            response = error
        return response.code, response.read().decode(), response.headers
    def token(self, path):
        status, html, _ = self.request(path)
        assert status == 200
        return re.search(r'name="_token" value="([a-f0-9]+)"', html).group(1)
    def login(self, address, password):
        return self.request('/login', {'_token':self.token('/login'), 'email':address, 'password':password})

client = Client()
assert client.request('/__settings_test')[1] == 'isolated-settings-test'
for route in ['/forgot-password', '/reset-password', '/auth/google']:
    assert client.request(route, {})[0] == 403
assert client.request('/auth/google/callback?state=bad&code=invalid')[0] == 302
mailbox = pathlib.Path(__file__).resolve().parents[1] / 'storage/mail'
before = set(mailbox.glob('*.eml'))
assert client.request('/forgot-password', {'_token':client.token('/forgot-password'), 'email':'new-user@example.test'})[0] == 302
known = client.request('/forgot-password')[1]
assert client.request('/forgot-password', {'_token':client.token('/forgot-password'), 'email':'absent@example.test'})[0] == 302
unknown = client.request('/forgot-password')[1]
message = 'If this address has a password-based account'
assert message in known and message in unknown
messages = [email.message_from_bytes(p.read_bytes()) for p in set(mailbox.glob('*.eml')) - before]
reset_mail = next(m for m in messages if str(m['Subject']) == 'Reset your Bunat Fabrics password')
body = reset_mail.get_payload(decode=True).decode()
secret = re.search(r'token=([a-f0-9]{32}\.[a-f0-9]{64})', body).group(1)
other_session = Client()
assert other_session.login('new-user@example.test','Changed456')[0] == 302
assert other_session.request('/settings')[0] == 200
status, _, headers = client.request('/reset-password?token=' + secret)
assert status == 302 and '?' not in headers['Location']
status, html, headers = client.request('/reset-password')
assert status == 200 and secret not in html and headers['Referrer-Policy'] == 'no-referrer'
assert client.request('/reset-password', {'_token':client.token('/reset-password'), 'password':'HttpReset789','password_confirmation':'HttpReset789'})[0] == 302
assert other_session.request('/settings')[0] == 302, 'old session remained valid'
assert client.login('new-user@example.test','Changed456')[2]['Location'].endswith('/login')
assert client.login('new-user@example.test','HttpReset789')[0] == 302
assert client.request('/settings')[0] == 200
assert client.request('/logout', {'_token':client.token('/settings')})[0] == 302
client.request('/reset-password?token=' + secret)
assert client.request('/reset-password', {'_token':client.token('/reset-password'), 'password':'Another123','password_confirmation':'Another123'})[0] == 422
print('PASS: recovery HTTP, private mailbox, CSRF, generic responses, single use, login and stale-session invalidation')
