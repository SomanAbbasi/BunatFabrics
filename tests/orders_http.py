"""End-to-end checkout checks. Run only against tests/catalog-router.php."""
import http.cookiejar
import json
import os
import re
import urllib.error
import urllib.parse
import urllib.request
import uuid

BASE = os.getenv('CATALOG_TEST_URL', 'http://127.0.0.1:8097')

class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *args, **kwargs):
        return None

class Client:
    def __init__(self):
        self.opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()), NoRedirect())

    def request(self, path, data=None):
        body = None if data is None else urllib.parse.urlencode(data).encode()
        try:
            response = self.opener.open(BASE + path, body)
        except urllib.error.HTTPError as error:
            response = error
        return response.code, response.read().decode(), response.headers

    def token(self, path):
        status, html, _ = self.request(path)
        assert status == 200, (path, status)
        return re.search(r'name="_token" value="([a-f0-9]+)"', html).group(1)

    def login(self, email, password):
        return self.request('/login', {'_token': self.token('/login'), 'email': email, 'password': password})

def check(condition, label):
    assert condition, label
    print('PASS:', label)

admin, customer, other, guest = Client(), Client(), Client(), Client()
check(guest.request('/__catalog_test')[1] == 'isolated-catalog-test', 'isolated server required')
check(guest.request('/checkout')[0] == 302, 'guest checkout requires login')
check(guest.request('/admin/orders')[0] == 302, 'guest denied admin')
check(guest.request('/admin/orders/status', {})[0] == 302, 'guest denied admin action')
check(guest.request('/api/admin/orders')[0] == 401, 'guest denied admin API')
check(admin.login('admin@example.test', 'TestAdmin123')[0] == 302, 'admin login')
check(admin.request('/checkout')[0] == 403, 'admin cannot place customer orders')
token = admin.token('/admin/categories/create')
suffix = uuid.uuid4().hex[:8]
category = {'_token':token,'name':'Integration '+suffix,'slug':'integration-'+suffix,'description':'','is_active':'1'}
check(admin.request('/admin/categories/store', category)[0] == 302, 'create test category')
html = admin.request('/admin/categories')[1]
category_id = re.search(r'/admin/categories/edit\?id=(\d+)', html).group(1)
product = {'_token':token,'product_code':'INT-'+suffix,'title':'Fabric '+suffix,'description':'Cotton fabric','category_id':category_id,'composition':'Cotton','width_inch':'58.5','weight_gsm':'120','unit':'yard','price_pkr':'19.99','status':'published'}
check(admin.request('/admin/products/store', product)[0] == 302, 'create test product')
html = admin.request('/admin/products')[1]
product_id = re.search(r'/admin/products/edit\?id=(\d+)', html).group(1)
second = dict(product, product_code='SECOND-'+suffix, title='Second fabric '+suffix, price_pkr='0.10', unit='meter')
check(admin.request('/admin/products/store', second)[0] == 302, 'create second test product')
second_id = re.search(r'/admin/products/edit\?id=(\d+)', admin.request('/admin/products')[1]).group(1)
check(customer.login('customer@example.test', 'TestCustomer123')[0] == 302, 'customer login')
check(customer.request('/admin/orders')[0] == 403, 'customer denied admin')
check(customer.request('/admin/orders/status', {})[0] == 403, 'customer denied admin action')
check(customer.request('/api/admin/orders')[0] == 403, 'customer denied admin API')
ctoken = customer.token('/cart')
check(customer.request('/cart/add', {'_token':ctoken,'product_id':product_id,'quantity':'3'})[0] == 302, 'add published fabric to cart')
check(customer.request('/cart/add', {'_token':ctoken,'product_id':second_id,'quantity':'2'})[0] == 302, 'add second fabric to cart')
check(customer.request('/checkout')[0] == 200, 'checkout shown')
fields = {'_token':ctoken,'phone':'+923001234567','address':'12 Test Street, Lahore','city':'Lahore','payment_method':'cod'}
check(customer.request('/checkout', dict(fields, _token='invalid'))[0] == 302, 'invalid CSRF rejected')
check(customer.request('/checkout', dict(fields, city='', payment_method='invalid'))[0] == 302, 'invalid checkout rejected')
invalid_html = customer.request('/checkout')[1]
check('City is required' in invalid_html and 'Choose a payment method' in invalid_html and '12 Test Street' in invalid_html, 'validation errors and entered values retained')
status, _, headers = customer.request('/checkout', fields)
check(status == 302 and '/checkout/success?order=' in headers['Location'], 'place order')
order_id = int(headers['Location'].split('order=')[1])
status, html, _ = customer.request('/checkout/success?order='+str(order_id))
check(status == 200 and '60.17' in html and '+923001234567' in html, 'customer confirmation')
check('Your cart is empty' in customer.request('/cart')[1], 'successful order clears cart')
status, html, _ = admin.request('/admin/orders')
check(status == 200 and product['title'] in html and '60.17' in html and 'Test Customer' in html and 'Pending' in html and 'Lahore' in html, 'same order appears in admin page')
status, body, _ = admin.request('/api/admin/orders')
rows = json.loads(body)['data']
order = next(row for row in rows if int(row['id']) == order_id)
check(status == 200 and order['customer_name'] == 'Test Customer' and order['subtotal_pkr'] == '60.17' and order['status'] == 'pending' and order['created_at'], 'admin API order data')
status, body, _ = admin.request('/api/admin/orders/show?id='+str(order_id))
order = json.loads(body)['data']; line = next(item for item in order['items'] if item['product_code'] == product['product_code'].upper())
check(status == 200 and line['title'] == product['title'] and line['unit'] == 'yard' and line['unit_price_pkr'] == '19.99' and str(line['quantity']) in ('3', '3.00') and line['line_total_pkr'] == '59.97', 'stored item snapshots')
check(len(order['items']) == 2 and any(item['line_total_pkr'] == '0.20' and item['unit'] == 'meter' for item in order['items']), 'second line and exact decimal total stored')
check(admin.request('/admin/orders/status', {'_token':'invalid','id':order_id,'expected':'pending','status':'confirmed'})[0] == 403, 'admin status CSRF rejected')
check(admin.request('/admin/orders/status', {'_token':token,'id':order_id,'expected':'pending','status':'confirmed'})[0] == 302, 'admin status update')
status, html, _ = admin.request('/admin/orders')
check(status == 200 and 'confirmed' in html and 'Test Administrator' in html, 'status history shown')
check(admin.request('/api/admin/orders?status=confirmed')[0] == 200, 'API accepts admin statuses')
newtoken = other.token('/signup')
signup = {'_token':newtoken,'name':'Other Customer','email':'other-'+suffix+'@example.test','password':'OtherCustomer123','password_confirmation':'OtherCustomer123'}
check(other.request('/signup', signup)[0] == 302, 'other customer signup')
check(other.login(signup['email'],signup['password'])[0] == 302, 'other customer login')
check(other.request('/checkout/success?order='+str(order_id))[0] == 404, 'other customer cannot view order')
print('Order integration checks complete; all fixtures are in the disposable database.')
