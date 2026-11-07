"""HTTP regression checks against settings_router.php and fresh account_settings.php fixtures."""
import http.cookiejar
import re
import urllib.error
import urllib.parse
import urllib.request

BASE = "http://127.0.0.1:8098"


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None


class Client:
    def __init__(self):
        self.cookies = http.cookiejar.CookieJar()
        self.opener = urllib.request.build_opener(NoRedirect(), urllib.request.HTTPCookieProcessor(self.cookies))

    def request(self, path, data=None):
        body = None if data is None else urllib.parse.urlencode(data).encode()
        try:
            response = self.opener.open(BASE + path, body, timeout=10)
        except urllib.error.HTTPError as error:
            response = error
        return response.code, response.read().decode(), response.headers

    def token(self, path):
        status, html, _ = self.request(path)
        assert status == 200, (path, status)
        return re.search(r'name="_token" value="([a-f0-9]+)"', html).group(1)

    def session(self):
        return next(cookie.value for cookie in self.cookies if cookie.name == "PHPSESSID")

    def login(self, email, password):
        return self.request("/login", {"_token": self.token("/login"), "email": email, "password": password})


guest = Client()
assert guest.request("/__settings_test")[1] == "isolated-settings-test", "Refusing a non-test server"
for path in ["/settings", "/admin/settings"]:
    assert guest.request(path)[0] == 302
    for action in ["/profile", "/password"]:
        assert guest.request(path + action, {})[0] == 302
print("PASS: all settings routes require authentication")

admin = Client()
customer = Client()
assert admin.login("new-admin@example.test", "Changed456")[0] == 302
assert customer.login("new-user@example.test", "Changed456")[0] == 302
assert customer.request("/admin/settings")[0] == 404
assert customer.request("/admin/settings/profile", {})[0] == 404
assert customer.request("/admin/settings/password", {})[0] == 404
assert admin.request("/settings")[2]["Location"].endswith("/admin/settings")
print("PASS: administrator authorization and customer route separation")

for client, path, email, other_email, other_id in [
    (customer, "/settings", "new-user@example.test", "new-admin@example.test", 1),
    (admin, "/admin/settings", "new-admin@example.test", "new-user@example.test", 2),
]:
    token = client.token(path)
    profile = {"_token": token, "name": "Updated Name", "email": email, "current_password": "Changed456"}
    for invalid_token in [{"_token": "bad"}, {"_token": ""}, {"_token": "", "_token[]": token}]:
        assert client.request(path + "/profile", profile | invalid_token)[0] == 403
        assert client.request(path + "/password", invalid_token)[0] == 403
    for invalid in [
        {"current_password": ""}, {"current_password": "WrongSecret123"},
        {"email": other_email}, {"name": "A"}, {"email": "bad-email"},
        {"name": "", "name[]": "Malformed"},
        {"current_password": "", "current_password[]": "Changed456"},
    ]:
        status, html, _ = client.request(path + "/profile", profile | invalid)
        assert status == 422, (path, invalid.keys(), status)
        assert "WrongSecret123" not in html and "Changed456" not in html
        assert 'aria-invalid="true"' in html
    original_session = client.session()
    # The submitted foreign ID and role must be ignored; only this session's user changes.
    assert client.request(path + "/profile", profile | {"name": "Own Account <script>", "id": other_id, "role": "admin"})[0] == 302
    assert client.session() != original_session
    status, html, _ = client.request(path)
    assert status == 200 and "Own Account &lt;script&gt;" in html and "<script>" not in html
    new_token = client.token(path)
    assert new_token != token
    assert client.request(path + "/profile", profile)[0] == 403
    if path == "/settings":
        assert customer.request("/admin/settings")[0] == 404
        assert "Own Account" not in admin.request("/admin/settings")[1]
    else:
        assert customer.request("/admin/settings")[0] == 404
    # Exercise email changes and then restore it so fixtures remain reusable.
    changed_email = "changed-" + email
    assert client.request(path + "/profile", profile | {"_token": new_token, "email": changed_email})[0] == 302
    assert changed_email in client.request(path)[1]
    assert client.request(path + "/profile", profile | {"_token": client.token(path)})[0] == 302

    password = {"_token": client.token(path), "current_password": "Changed456", "new_password": "Secure789", "new_password_confirmation": "Secure789"}
    for invalid in [{"current_password": "WrongSecret123"}, {"new_password_confirmation": "Different123"}, {"new_password": ""}, {"new_password": "Changed456", "new_password_confirmation": "Changed456"}]:
        status, html, _ = client.request(path + "/password", password | invalid)
        assert status == 422 and "WrongSecret123" not in html and "Secure789" not in html
    old_session = client.session()
    assert client.request(path + "/password", password)[0] == 302
    assert client.session() != old_session
    assert client.request(path)[0] == 200
    assert client.token(path) != password["_token"]
    assert client.request("/logout", {"_token": client.token(path)})[0] == 302
    assert client.request(path)[0] == 302
    assert client.login(email, "Changed456")[2]["Location"].endswith("/login")
    assert "credentials do not match" in client.request("/login")[1]
    assert client.login(email, "Secure789")[0] == 302
    assert client.request(path)[0] == 200
    assert client.request(path + "/password", {"_token": client.token(path), "current_password": "Secure789", "new_password": "Changed456", "new_password_confirmation": "Changed456"})[0] == 302
    print(f"PASS: {path} validation, CSRF, ownership, escaping, session renewal, password login/logout")

signup = Client()
assert signup.request("/signup", {"_token": signup.token("/signup"), "name": "HTTP Customer", "email": "http-signup@example.test", "password": "Signup123", "password_confirmation": "Signup123"})[0] == 302
assert signup.login("http-signup@example.test", "Signup123")[0] == 302
assert signup.request("/settings")[0] == 200
for path in ["/", "/dashboard", "/orders"]:
    assert customer.request(path)[0] == 200, path
for path in ["/admin", "/admin/products", "/admin/categories", "/admin/orders"]:
    assert admin.request(path)[0] == 200, path
print("PASS: signup and existing customer/admin pages")
