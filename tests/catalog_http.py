"""HTTP regression checks; only runs against the isolated test router marker."""
import http.cookiejar
import re
import sys
import urllib.error
import urllib.parse
import urllib.request
import uuid

BASE = sys.argv[1] if len(sys.argv) > 1 else 'http://127.0.0.1:8097'
class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *args, **kwargs):
        return None

class Client:
    def __init__(self):
        self.jar = http.cookiejar.CookieJar()
        self.opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(self.jar), NoRedirect())
    def request(self, path, data=None):
        body = None if data is None else urllib.parse.urlencode(data).encode()
        try:
            response = self.opener.open(BASE + path, body)
        except urllib.error.HTTPError as error:
            response = error
        return response.code, response.read().decode(), response.headers
    def token(self, path):
        status, body, _ = self.request(path)
        assert status == 200, (path, status, body)
        return re.search(r'name="_token" value="([a-f0-9]+)"', body).group(1)
    def login(self, email, password):
        return self.request('/login', {'_token': self.token('/login'), 'email': email, 'password': password})

def check(condition, label):
    assert condition, label
    print('PASS:', label)

admin, customer, guest = Client(), Client(), Client()
check(guest.request('/__catalog_test')[1] == 'isolated-catalog-test', 'isolated server required')
for section in ['categories', 'products']:
    for path in ['', '/create', '/edit?id=1']:
        check(guest.request('/admin/' + section + path)[0] == 302, 'guest GET denied: ' + section + path)
    for path in ['/store', '/update', '/state']:
        check(guest.request('/admin/' + section + path, {})[0] == 302, 'guest POST denied: ' + section + path)
check(customer.login('customer@example.test', 'TestCustomer123')[0] == 302, 'customer login')
for section in ['categories', 'products']:
    for path in ['', '/create', '/edit?id=1']:
        check(customer.request('/admin/' + section + path)[0] == 403, 'customer GET denied: ' + section + path)
    for path in ['/store', '/update', '/state']:
        check(customer.request('/admin/' + section + path, {})[0] == 403, 'customer POST denied: ' + section + path)
check('Customer account' in customer.request('/dashboard')[1], 'customer dashboard renders account details')
check(admin.login('admin@example.test', 'TestAdmin123')[0] == 302, 'admin login')
check(admin.request('/dashboard')[2]['Location'].endswith('/admin'), 'admin dashboard redirect')
check(admin.request('/admin')[0] == 200, 'admin dashboard render')
for section in ['categories', 'products']:
    for action in ['store', 'update', 'state']:
        for bad in [{}, {'_token': 'bad'}, {'_token[]': 'bad'}]:
            check(admin.request('/admin/' + section + '/' + action, bad)[0] == 403, 'CSRF rejected: ' + section + '/' + action)
check(admin.request('/admin/products/edit?id[]=1')[0] == 404, 'malformed ID rejected')
suffix = uuid.uuid4().hex[:8]
token = admin.token('/admin/categories/create')
category = {'_token':token, 'name':'HTTP Cotton ' + suffix, 'slug':'http-cotton-' + suffix, 'description':'Natural cotton', 'is_active':'1'}
check(admin.request('/admin/categories/store', category)[0] == 302, 'HTTP create category')
status, body, _ = admin.request('/admin/categories')
check(status == 200 and category['name'] in body, 'HTTP category listing')
category_id = re.search(r'/admin/categories/edit\?id=(\d+)', body).group(1)
check(admin.request('/admin/categories/store', category)[0] == 422, 'HTTP duplicate category rejected')
category['id'] = category_id
category['description'] = 'Updated description'
check(admin.request('/admin/categories/update', category)[0] == 302, 'HTTP edit category')
product = {'_token':token, 'product_code':'HTTP-' + suffix, 'title':'HTTP fabric ' + suffix, 'description':'<script>alert(1)</script>', 'category_id':category_id, 'composition':'100% cotton', 'width_inch':'58.5', 'weight_gsm':'120', 'unit':'meter', 'status':'draft'}
check(admin.request('/admin/products/store', product)[0] == 302, 'HTTP create draft')
status, body, _ = admin.request('/admin/products')
check(status == 200 and product['title'] in body, 'HTTP product listing')
product_id = re.search(r'/admin/products/edit\?id=(\d+)', body).group(1)
check(product['title'] not in guest.request('/')[1], 'draft hidden on homepage')
status, body, _ = admin.request('/admin/products/store', product)
check(status == 422 and product['title'] in body and 'already in use' in body, 'duplicate code preserves form values')
status, body, _ = admin.request('/admin/products/store', dict(product, category_id='999999', product_code='BAD-' + suffix))
check(status == 422 and 'Choose an existing category' in body, 'HTTP invalid category')
product['id'] = product_id
product['title'] += ' edited'
check(admin.request('/admin/products/update', product)[0] == 302, 'HTTP edit product')
check(admin.request('/admin/products/state', {'_token':token, 'id':product_id, 'status':'published'})[0] == 302, 'HTTP publish')
body = guest.request('/')[1]
check(product['title'] in body and '&lt;script&gt;' in body and '<script>alert(1)</script>' not in body, 'public product visible and HTML escaped')
check(admin.request('/admin/categories/state', {'_token':token,'id':category_id,'is_active':'0'})[0] == 302, 'HTTP hide category')
check(product['title'] not in guest.request('/')[1], 'hidden category excluded publicly')
check(admin.request('/admin/categories/state', {'_token':token,'id':category_id,'is_active':'1'})[0] == 302, 'HTTP enable category')
check(product['title'] in guest.request('/')[1], 'enabled category visible publicly')
check(admin.request('/admin/products/state', {'_token':token,'id':product_id,'status':'archived'})[0] == 302, 'HTTP archive')
check(product['title'] not in guest.request('/')[1], 'archive excluded publicly')
check(admin.request('/admin/products/state', {'_token':token,'id':product_id,'status':'draft'})[0] == 302, 'HTTP unpublish')
# Leave one published test fabric for visual inspection in the isolated database.
admin.request('/admin/products/state', {'_token':token,'id':product_id,'status':'published'})
check(admin.request('/logout', {'_token':'invalid'})[0] == 302 and admin.request('/admin')[0] == 200, 'invalid logout token preserves login')
check(admin.request('/logout', {'_token':token})[0] == 302 and admin.request('/admin')[0] == 302, 'valid logout ends session')
newuser = Client()
newtoken = newuser.token('/signup')
signup = {'_token':newtoken,'name':'HTTP New User','email':'new-' + suffix + '@example.test','password':'NewCustomer123','password_confirmation':'NewCustomer123'}
check(newuser.request('/signup', signup)[0] == 302, 'signup submission')
check(newuser.login(signup['email'], signup['password'])[0] == 302 and signup['name'] in newuser.request('/dashboard')[1], 'new signup can log in')
check(newuser.request('/admin/products')[0] == 403, 'signup cannot create administrator')
print('HTTP suite complete; test data exists only in the disposable database.')
