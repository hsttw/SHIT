jQuery(document).ready(function(event){

    var url = 'http://localhost:9000/';
    var isAnimating = false,
    firstLoad = false;

    $(document).keypress(function(e) {
        $('.error').hide();
        if(e.which == 13) {
            $('.cd-btn').click();
        }
    });

    $('main').on('click', '[data-type="page-transition"]', function(event){

        event.preventDefault();
        user = $('.cd-input[name="username"]').val();
        pass = $('.cd-input[name="password"]').val();
        if (user.length > 0 && pass.length > 0) {

            var xhr = $.ajax({
                crossDomain: true,
                jsonpCallback: "ap",
                dataType: "jsonp",
                contentType: "application/json",
                type: "POST",
                url: url + '/admin/login.php',
                data: "username="+user+"&password="+pass,
                success: function(res){
                    if (res.status == 'success') {
                        loginSuccess(res.content.session);
                    }

                    if (res.status == 'failure') {
                        $('.error>h2').text(res.msg);
                        $('.error').show();
                    }
                }
            });


        } else {
            $('.error').show();
        }
    });

    function loginSuccess(s)
    {
        var now = new Date();
        var time = now.getTime();
        time += 3600 * 1000;
        now.setTime(time);
        document.cookie = 's='+s + '; expires=' + now.toUTCString() + '; path=/';
        var newPage = $('.cd-btn').attr('url');
        if( !isAnimating ) changePage(newPage, true);
        firstLoad = true;
    }

    $(window).on('popstate', function() {
        if( firstLoad ) {
            var newPageArray = location.pathname.split('/'),
                newPage = newPageArray[newPageArray.length - 1];
            if( !isAnimating ) changePage(newPage, false);
        }
        firstLoad = true;
    });

    function changePage(url, bool) {
        isAnimating = true;
        $('body').addClass('page-is-changing');
        $('.cd-loading-bar').one('webkitTransitionEnd otransitionend oTransitionEnd msTransitionEnd transitionend', function(){

            setTimeout(function(){
                loadNewContent(url);
            }, 1500);

            $('.cd-loading-bar').off('webkitTransitionEnd otransitionend oTransitionEnd msTransitionEnd transitionend');
        });
        if( !transitionsSupported() ) loadNewContent(url, bool);
    }

    function loadNewContent(url) {
        location.replace(url);
    }

    function transitionsSupported() {
        return $('html').hasClass('csstransitions');
    }
});
