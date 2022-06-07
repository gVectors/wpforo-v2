/* global wpforo */

jQuery(document).ready(function ($) {
    $(document).on('click', '#wpforo-admin-notice-recaptcha .notice-dismiss', function () {
        var wrap = $(this).closest('#wpforo-admin-notice-recaptcha')
        $.ajax({
            type: 'POST', url: ajaxurl, data: {
                backend: 1, action: 'wpforo_dissmiss_recaptcha_note'
            }
        }).done(function (response) {
            if (parseInt(response)) {
                wrap.remove()
            }
        })
    })

    var fas = $('[name$="[icon]"]')
    fas.each(function () {
        var fa = $(this)
        fa.iconpicker({
            placement: 'top',
            selectedCustomClass: 'wpf-fa-ico-primary-bg',
            component: '.wpf-fa-ico-preview',
            collision: true,
            animation: true
        })
        var w = document.createElement('span')
        $(w).addClass('wpf-input-group-addon')
        var i = document.createElement('i')
        $(i).addClass(fa.val())
        w.append(i)
        fa.after(w)
        fa.on('iconpickerUpdated input propertychange', function () {
            $(i).removeClass().addClass($(this).val())
        })
    });

    wpforo_scrollto_setting_field(window.location);
    window.onhashchange = function(){
        wpforo_scrollto_setting_field(window.location);
    };

    /*initiate the autocomplete function on the "myInput" element, and pass along the countries array as possible autocomplete values:*/
    autocomplete(document.getElementById("wpf-opt-search-field"), wpforo.settings.info);
});

function get_parentid (arr, depth) {
    for (var i = arr.length - 1; i >= 0; i--) {
        if (arr[i]['depth'] == depth) return arr[i]['forumid']
    }
}

function get_forums_hierarchy () {

    var ul_content = document.getElementById('menu-to-edit')
    var lis = ul_content.getElementsByTagName('LI')

    var forums_hierarchy_arr = new Array()

    for (var i = 0; i < lis.length; i++) {
        forums_hierarchy_arr[i] = new Array()
        forums_hierarchy_arr[i]['forumid'] = lis[i].id.replace('menu-item-', '')
        var depth = lis[i].getAttribute('class').replace('menu-item menu-item-depth-', '')

        forums_hierarchy_arr[i]['depth'] = depth

        if (depth == 0) {
            forums_hierarchy_arr[i]['parentid'] = 0
        } else {
            var previous_depth = depth - 1
            forums_hierarchy_arr[i]['parentid'] = get_parentid(forums_hierarchy_arr, previous_depth)
        }

        forums_hierarchy_arr[i]['order'] = i + 1

        var h_id = 'forumid-' + forums_hierarchy_arr[i]['forumid']
        var h_parentid = 'parentid-' + forums_hierarchy_arr[i]['forumid']
        var h_order = 'order-' + forums_hierarchy_arr[i]['forumid']

        document.getElementById(h_id).value = forums_hierarchy_arr[i]['forumid']
        document.getElementById(h_parentid).value = forums_hierarchy_arr[i]['parentid']
        document.getElementById(h_order).value = forums_hierarchy_arr[i]['order']

    }

    document.getElementById('forum-hierarchy').submit()

}

function mode_changer (v) {
    if (v) {
        document.getElementById('forum_submit').value = wpforo_admin.phrases.move
        document.getElementById('forum_select').disabled = false
    } else {
        document.getElementById('forum_submit').value = wpforo_admin.phrases.delete
        document.getElementById('forum_select').disabled = true
    }
}

function select_all () {
    var sel_all = document.getElementById('cb-select-all-1')
    var list = document.getElementById('the-list').getElementsByTagName('INPUT')
    for (var i = 0; i < list.length; i++)  document.getElementById(list[i].id).checked = !!sel_all.checked
}

function costum_or_inherit() {
    var chack = document.getElementById('custom')
    document.getElementById('permis').disabled = !!chack.checked;
}

function mode_changer_ug(v) {
    document.getElementById('ug_select').disabled = !v;
}

function wpforo_scrollto_setting_field( location ){
    if( location.hash ){
        var scroll_to;
        var exreg = new RegExp('&wpf_tab=([^=&?#\\s]+)', 'i');
        var match = location.href.match(exreg);
        if( match ){
            var hash = location.hash.toString().slice(1);
            var name = match[1] + '\\['+ hash +'\\]';
            var wrap_row = jQuery( '[data-wpf-opt="'+ hash +'"]' );
            if( wrap_row.length ){
                scroll_to = wrap_row.offset().top - 150;
                wpForoScrollTo(scroll_to, function () {
                    wrap_row.addClass("wpf-selected");
                    if( ! jQuery( '#wpf-opt-search-field' ).is(':focus') ){
                        var field = jQuery( '[name^='+ name +']', wrap_row );
                        field.trigger('focus');
                        field.trigger('select');
                    }
                    setTimeout(function () {
                        wrap_row.removeClass("wpf-selected");
                    }, 3000);
                });
            }
        }
    }
}

function wpforo_admin_tinymce_setup(editor){
    editor.on('init', function(e) {
        wpforo_scrollto_setting_field(window.location);
    });
}

function wpForoScrollTo(top, callback, element) {
    var html = element ? element : document.documentElement;
    var current = html.scrollTop;
    var delta = top - current;
    var finish = function () {
        html.scrollTop = top;
        if (callback) {
            callback();
        }
    };
    if (!window.performance.now || delta === 0) {
        finish();
        return;
    }
    var transition = wpForoEaseOutQuad;
    var max = 300;
    if (delta < -max) {
        current = top + max;
        delta = -max;
    } else if (delta > max) {
        current = top - max;
        delta = max;
    } else {
        transition = wpForoEaseInOutQuad;
    }
    var duration = 150;
    var interval = 7;
    var time = window.performance.now();
    var animate = function () {
        var now = window.performance.now();
        if (now >= time + duration) {
            finish();
            return;
        }
        var dt = (now - time) / duration;
        html.scrollTop = Math.round(current + delta * transition(dt));
        setTimeout(animate, interval);
    };
    setTimeout(animate, interval);
}

function wpForoEaseOutQuad(t) {
    return t * t;
}

function wpForoEaseInOutQuad(t) {
    return (t < 0.5) ? (2 * t * t) : ((4 - 2 * t) * t - 1);
}


// ----------- autocomplate settings search ----------------- //

function autocomplete(inp, arr) {
    var m;
    var page = '';
    var page_regexp = new RegExp('[?&]page=([^=&?#\\s]+)', 'i');
    m = window.location.href.match(page_regexp);
    if( m ) page = m[1];

    var wpf_tab = '';
    var wpf_tab_regexp = new RegExp('[?&]wpf_tab=([^=&?#\\s]+)', 'i');
    m = window.location.href.match(wpf_tab_regexp);
    if( m ) wpf_tab = m[1];

    /*the autocomplete function takes two arguments,
     the text field element and an array of possible autocompleted values:*/
    var currentFocus;
    /*execute a function when someone writes in the text field:*/
    inp.addEventListener("input", function() {
        var a, b, val = this.value;
        /*close any already open lists of autocompleted values*/
        closeAllLists();
        if (!val) { return false;}
        currentFocus = -1;
        /*create a DIV element that will contain the items (values):*/
        a = document.createElement("DIV");
        a.setAttribute("id", this.id + "-autocomplete-list");
        a.setAttribute("class", "autocomplete-items");
        /*append the DIV element as a child of the autocomplete container:*/
        this.parentNode.appendChild(a);
        /*for each item in the array...*/

        var pattern = val.trim().split(/\s+/).filter(function(value){ return value.length > 2 }).join('|');
        if( !pattern ) return;
        var exreg = new RegExp( pattern, 'iug' );
        var relevancy = [];
        for( var i in arr.core ){
            var ss,st,sto,sd,sdo;
            var s = arr.core[i];
            if( (ss = i.match( exreg ))
                || (s['title'] && (st = s['title'].match( exreg )))
                || (s['title_original'] && (sto = s['title_original'].match( exreg )))
                || (s['description'] && (sd = s['description'].match( exreg )))
                || (s['description_original'] && (sdo = s['description_original'].match( exreg ))) ){
                relevancy.push( {
                    setting : s,
                    option  : {},
                    label   : s['title'],
                    wpf_tab : i,
                    optname : '',
                    points  :  (ss  ?  ss.length * 3 : 0)
                             + (st  ?  st.length * 2 : 0)
                             + (sto ? sto.length * 2 : 0)
                             + (sd  ?  sd.length     : 0)
                             + (sdo ? sdo.length     : 0)
                });
            }

            var os,ol,olo,od,odo;
            for (var k in s['options']) {
                var o = s['options'][k];
                if( ( os = k.match( exreg ) )
                    || (o['label'] && (ol = o['label'].match( exreg )))
                    || (o['label_original'] && (olo = o['label_original'].match( exreg )))
                    || (o['description'] && (od = o['description'].match( exreg )))
                    || (o['description_original'] && (odo = o['description_original'].match( exreg ))) ){

                    relevancy.push( {
                        label   : o['label'],
                        setting : s,
                        option  : o,
                        wpf_tab : i,
                        optname : k,
                        points  :  (os  ?  os.length * 4 : 0)
                                 + (ol  ?  ol.length * 3 : 0)
                                 + (olo ? olo.length * 3 : 0)
                                 + (od  ?  od.length * 2 : 0)
                                 + (odo ? odo.length * 2 : 0)
                    });
                }
            }

        }

        relevancy.sort( function(a,b){ return b.points - a.points } );
        relevancy.forEach( function(value){
            var _page = value.setting.base && wpforo.board.has_more_boards ? 'wpforo-base-settings' : page;
            if( !value.setting.base && _page === 'wpforo-base-settings' ) _page = 'wpforo-settings';
            b = document.createElement("DIV");
            if( value.optname ){
                b.innerHTML = `<strong>OPTION:</strong>&nbsp;<a href="admin.php?page=${ _page }&wpf_tab=${ value.wpf_tab }#${ value.optname }">${ value.label } #${ value.optname }</a>`;
            }else{
                b.innerHTML = `<strong>SETTINGS:</strong>&nbsp;<a href="admin.php?page=${ _page }&wpf_tab=${ value.wpf_tab }">${ value.label }</a>`;
            }
            a.appendChild(b);
        });

        var scrto = relevancy.find( function( value ){ return value.wpf_tab === wpf_tab && value.optname });
        if( scrto && window.location.hash !== '#' + scrto.optname ){
            closeAllLists();
            window.location.hash = '';
            window.location.hash = scrto.optname;
        }

    });
    /*execute a function presses a key on the keyboard:*/
    inp.addEventListener("keydown", function(e) {
        var x = document.getElementById(this.id + "-autocomplete-list");
        if (x) x = x.getElementsByTagName("div");
        if (e.code === 'ArrowDown') {
            /*If the arrow DOWN key is pressed,
             increase the currentFocus variable:*/
            currentFocus++;
            /*and make the current item more visible:*/
            addActive(x);
        } else if (e.code === 'ArrowUp') { //up
            /*If the arrow UP key is pressed,
             decrease the currentFocus variable:*/
            currentFocus--;
            /*and make the current item more visible:*/
            addActive(x);
        }else if (e.code === 'Home') {
            currentFocus = 0;
            addActive(x);
        }else if (e.code === 'End') {
            currentFocus = x.length - 1;
            addActive(x);
        } else if (e.code === 'Enter') {
            /*If the ENTER key is pressed, prevent the form from being submitted,*/
            e.preventDefault();
            if (currentFocus > -1) {
                /*and simulate a click on the "active" item:*/
                if (x) x[currentFocus].querySelector('a').click();
            }
        }
    });
    function addActive(x) {
        /*a function to classify an item as "active":*/
        if (!x) return false;
        /*start by removing the "active" class on all items:*/
        removeActive(x);
        if (currentFocus >= x.length) currentFocus = 0;
        if (currentFocus < 0) currentFocus = (x.length - 1);
        var autocomplete_items = document.querySelector( '.autocomplete-items' );
        /*add class "autocomplete-active":*/
        x[currentFocus].classList.add("autocomplete-active");
        var delta = x[currentFocus].offsetTop - autocomplete_items.scrollTop;
        if( delta < 0 || delta > 300 ){
            wpForoScrollTo( x[currentFocus].offsetTop - 117, undefined, document.querySelector( '.autocomplete-items' ) );
        }
    }
    function removeActive(x) {
        /*a function to remove the "active" class from all autocomplete items:*/
        for (var i = 0; i < x.length; i++) {
            x[i].classList.remove("autocomplete-active");
        }
    }
    function closeAllLists(elmnt) {
        /*close all autocomplete lists in the document,
         except the one passed as an argument:*/
        var x = document.getElementsByClassName("autocomplete-items");
        for (var i = 0; i < x.length; i++) {
            if (elmnt != x[i] && elmnt != inp) {
                x[i].parentNode.removeChild(x[i]);
            }
        }
    }
    /*execute a function when someone clicks in the document:*/
    document.addEventListener("click", function (e) { closeAllLists(e.target); });
}
