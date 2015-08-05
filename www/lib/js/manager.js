var pagesize    = 1;
var page_number = 1;
var session     = getCookie('s');

if (s == undefined) {
    alert('logot');
}

var xhr = $.ajax({
    crossDomain: true,
    jsonpCallback: "ap",
    dataType: "jsonp",
    contentType: "application/json",
    type: "POST",
    url: '/admin/get_content_list.php',
    data: "pagesize="+pagesize+'&page_number='+page_number+'&s='+session,
    success: function(res){
        if (res.status == 'success') {

            // append tags
            var items = [];
            $.each(res.content, function(i, item) {
                items.push(' \
                    <li> \
                    <code id='+item.id+'>  \
                       '+item.device_id+' \
                     / '+item.username+' \
                     / '+item.password+' \
                     / '+item.browser_agent+' \
                     / '+item.create_date+' \
                    </code> \
                    </li> \
                ');
            });
            $('#conn_user>.bs-glyphicons-list').append( items.join('') );
            $( "#conn_user>.bs-glyphicons-list> li> code" ).click( function(e) {
                $.ajax({
                    crossDomain: true,
                    jsonpCallback: "ap",
                    dataType: "jsonp",
                    contentType: "application/json",
                    type: "POST",
                    url: '/admin/get_content.php',
                    data: 'id='+$(this).attr('id')+'&s='+session,
                    success: function(res){
                        if (res.status == 'success') {
                            var itemss = [];
                            $.each(res.content, function(i, item) {
                                console.log(item);
                            });
                        }
                    }
                });
            });
        }

        if (res.status == 'failure') {
            console.log(res);
        }
    }
});

function getCookie(name)
{
    var arr = document.cookie.match(new RegExp("(^| )"+name+"=([^;]*)(;|$)"));
        if(arr != null) return unescape(arr[2]); return null;
}






















