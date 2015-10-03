param = window.location.pathname;

console.log(param);

var router = {
    '/' : 'index',
    '/index.html' : 'index',
    '/history.html' : 'history',
};

if (typeof router[param] != 'undefined') {
    var s = document.createElement("script");
    s.type = "text/javascript";
    s.src = '/lib/js/' + router[param] + '.js';
    $("head").append(s);

} else {
    console.log(false);
}
