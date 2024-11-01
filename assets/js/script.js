function updateSettings(result) {

    document.getElementById('price_multi').value = result.price_multi;
    document.getElementById('sale_multi').value = result.sale_multi;
    let takali_opt = '';
    for (let i = 0; i < result.categories.length; i++) {
        //console.log(settings.categories[i]);
        if (result.categories[i].default === 1) {
            takali_opt = takali_opt + '<option value="' + result.categories[i].id + '" selected>' + result.categories[i].name + '</option>';
        } else {
            takali_opt = takali_opt + '<option value="' + result.categories[i].id + '">' + result.categories[i].name + '</option>';
        }
    }
    document.getElementById('category').innerHTML = takali_opt;
    document.getElementById('download_images').checked = (result.download_images === 1) ? true : false;
    document.getElementById('import_product_images_limit').value = result.import_product_images_limit;
    document.getElementById('import_short_description').checked = (result.import_short_description === 1) ? true : false;
    document.getElementById('import_long_description').checked = (result.import_long_description === 1) ? true : false;
    document.getElementById('import_attributes').checked = (result.import_attributes === 1) ? true : false;
    let takali_status = '', takali_status_list = ['Publish', 'Draft'];

    for (let i = 0; i < takali_status_list.length; i++) {
        //console.log(settings.categories[i]);
        if (takali_status_list[i] === result.default_product_status) {
            takali_status = takali_status + '<option value="' + takali_status_list[i] + '" selected>' + takali_status_list[i] + '</option>';
        } else {
            takali_status = takali_status + '<option value="' + takali_status_list[i] + '">' + takali_status_list[i] + '</option>';
        }
    }
    document.getElementById('default_product_status').innerHTML = takali_status;
    document.getElementById('manage_stock').checked = (result.manage_stock === 1) ? true : false;
    document.getElementById('use_random_stock').checked = (result.use_random_stock === 1) ? true : false;
    document.getElementById('random_stock_min').value = result.random_stock_min;
    document.getElementById('random_stock_max').value = result.random_stock_max;
    document.getElementById('use_sku_number').checked = (result.use_sku_number === 1) ? true : false;
    document.getElementById('taknalogy_reviews').checked = (result.taknalogy_reviews === 'yes') ? true : false;
    document.getElementById('taknalogy_reviews_widget').checked = (result.taknalogy_reviews_widget === 'yes') ? true : false;
    document.getElementById('shop_url').value = result.shopurl;
    document.getElementById('access_string').value = result.auth_key;
}

function makeCall(command, data) {

    fetch(takaliajaxurl + '?action=tak_ajax_ali_dash_admin&command=' + command + '&_wpnonce=' + _nonce,
        {
            method: 'POST', // *GET, POST, PUT, DELETE, etc.
            mode: 'cors', // no-cors, *cors, same-origin
            cache: 'no-cache', // *default, no-cache, reload, force-cache, only-if-cached
            credentials: 'include', // include, *same-origin, omit
            headers: {
                'Content-Type': 'application/json'
            },
            redirect: 'follow', // manual, *follow, error
            referrer: 'no-referrer', // no-referrer, *client
            timeout: 5000,
            body: JSON.stringify(data)
        }).then(response => {
            return response.json();
        }).then(result => {
            //console.log(result);

            if (result.success === true) {
                if (result.action === "update") {
                    //console.log('update');
                    //console.log(command);
                    updateSettings(result);
                    let feedmsg = document.getElementById('settings_tab_update_notice');
                    if (command === "default") {
                        feedmsg.innerHTML = "<p>Click <strong>Save Changes,</strong>.</p>";
                        feedmsg.style.display = "block";
                    }
                } else if (result.action === "noupdate") {
                    //console.log('noupdate');
                    let feedmsg = document.getElementById('settings_tab_update_notice');
                    feedmsg.innerHTML = "<p>Settings updated.</p>";
                    feedmsg.style.display = "block";
                } else if (result.action === "keyupdate") {
                    //console.log('noupdate');
                    let feedmsg = document.getElementById('keys_tab_update_notice');
                    feedmsg.innerHTML = '<p>Keys updated. Please use new keys for <a href="https://chrome.google.com/webstore/detail/taknalogy-aliexpress-drop/gbbfmfcdncigpkaefnocojojbncliafb" target="_blank">chrome extension</a>.</p>';
                    feedmsg.style.display = "block";
                }
            } else if (result.success === false) {
                let feedmsg = document.getElementById('settings_tab_update_notice');
                feedmsg.innerHTML = "<p>Operation was not successful.</p>";
                feedmsg.style.display = "block";
            } else {
                console.log('error');
            }
        }).catch(function () {
            console.log("error");
        });
}
function taknalogygenkeys(event) {
    event.preventDefault();
    var n = Math.floor(Math.random() * 11);
    var k = Math.floor(Math.random() * 1000000);
    var m = String.fromCharCode(n) + k;
    document.getElementById('access_string').value = uniqid(Math.random(), true);
    let feedmsg = document.getElementById('keys_tab_update_notice');
    feedmsg.innerHTML = "<p>Click <strong>Update,</strong> to save changes.</p>";
    feedmsg.style.display = "block";
}
function taknalogyupdatekeys(e) {
    e.preventDefault();
    var object = {};
    object['shopurl'] = document.getElementById('shop_url').value;
    object['auth_key'] = document.getElementById('access_string').value;
    console.log(object);
    makeCall('updatekey', object);
}
function uniqid(a = "", b = false) {
    var c = Date.now() / 1000;
    var d = c.toString(16).split(".").join("");
    while (d.length < 14) {
        d += "0";
    }
    var e = "";
    if (b) {
        e = ".";
        var f = Math.round(Math.random() * 100000000);
        e += f;
    }
    return a + d + e;
}
window.addEventListener('load', function load(event) {
    document.querySelectorAll('a[data-toggle= "tab"]').forEach(item => {
        item.addEventListener("click", function (e) {
            if (e.target.id === "settings-tab") {
                document.getElementById('settings_tab_update_notice').style = "none";
            } else if (e.target.id === "keys-tab") {
                document.getElementById('keys_tab_update_notice').style = "none";
            }
        });
    });
    document.getElementById('regenerate').addEventListener('click', taknalogygenkeys);
    document.getElementById('taknalogykeys').addEventListener('submit', (e) => taknalogyupdatekeys(e));
    document.getElementById('get_product_form').addEventListener('submit', taknalogyprodform);
    document.getElementById("get_product_images").addEventListener("submit", taknalogylogSubmit);
    makeCall('settings', {});
    document.getElementById('defaultsettings').addEventListener('click', (e) => {
        e.preventDefault();
        makeCall('default', null);
    });
    document.getElementById('settings').addEventListener('submit', (e) => {
        e.preventDefault();
        let formData = new FormData(e.target);
        var object = {};
        formData.forEach((value, key) => {
            // Reflect.has in favor of: object.hasOwnProperty(key)
            if (value === 'on') {
                value = 1;
            }
            if (!Reflect.has(object, key)) {
                object[key] = value;
                return;
            }
            if (!Array.isArray(object[key])) {
                object[key] = [object[key]];
            }
            object[key].push(value);
        });
        //var json = JSON.stringify(object);
        makeCall('update', object);
        // console.log(object);
    });
});
function taknalogyproductonsale(e) {
    const sale_multiplier = document.getElementById('p_sale_multiplier');
    if (e.srcElement.checked) {
        sale_multiplier.style.display = "table-row";
    } else {
        sale_multiplier.style.display = "none";
    }
}
function taknalogyfullimport(e) {
    const controls = document.getElementById('controls');
    if (e.srcElement.checked) {
        controls.style.display = "none";
    } else {
        controls.style.display = "block";
    }
}
//jQuery(document).ready(function () {
//    const form = document.getElementById('get_product_form');
//    form.addEventListener('submit', taknalogyprodform);
//});
/*
jQuery(document).ready(function () {
    const form = document.getElementById('get_product_images');
    form.addEventListener('submit', logSubmit);
});
*/
(function () {
    if (typeof window.CustomEvent === "function") return false;
    function CustomEvent(event, params) {
        params = params || { bubbles: true, cancelable: true, detail: undefined };
        var evt = document.createEvent('submit');
        evt.initCustomEvent(event, params.bubbles, params.cancelable, params.detail);
        return evt;
    }
    CustomEvent.prototype = window.Event.prototype;
    window.CustomEvent = CustomEvent;
})();
var evt = new CustomEvent("submit", { "bubbles": true, "cancelable": true });
var buttonPressed = '';
//jQuery(document).ready(function () {
//    document.getElementById("get_product_images").addEventListener("submit", taknalogylogSubmit);
//});
/*
function (event) {
    event.preventDefault();
    alert('submit');
});
*/
function taknalogyprodform(event) {
    event.preventDefault();
    buttonPressed = '';
    document.getElementById('created_product_url').value = document.getElementById('new_product_url').value;
    document.getElementById('product_onsale_hidden').value = document.getElementById('product_onsale').checked;
    document.getElementById('regular_multiplier_hidden').value = document.getElementById('regular_multiplier').value;
    document.getElementById('sale_multiplier_hidden').value = document.getElementById('sale_multiplier').value;
    document.getElementById('delivery_charge_hidden').value = document.getElementById('delivery_charge').value;
    //document.getElementById('product_type_hidden').value = document.getElementById('delivery_charge').value;
    //}
    const log = document.getElementById('server-results');
    //if (document.activeElement.id === 'get_base_product') {
    log.textContent = tak_ali_cre_pr + (new Date()).toLocaleTimeString();
    document.getElementById('get_base_product_status').style.display = "none";
    document.getElementById('submit_images_status').style.display = "none";
    document.getElementById('submit_attributes_status').style.display = "none";
    document.getElementById('submit_var_data_status').style.display = "none";
    document.getElementById('submit_save_var_status').style.display = "none";
    //}
    taknalogycust_submit(this, event, log);
}
function taknalogylogSubmit(event) {
    event.preventDefault();
    //    console.log(buttonPressed);
    const log = document.getElementById('server-results');
    //console.log(event);
    //if (event.srcElement.id === 'get_product_form') {
    if (document.activeElement.id === 'get_images' || buttonPressed === 'get_images') {
        log.textContent = tak_ali_cre_im + (new Date()).toLocaleTimeString();
    } else if (document.activeElement.id === 'create_attributes' || buttonPressed === 'create_attributes') {
        log.textContent = tak_ali_cre_att + (new Date()).toLocaleTimeString();
    } else if (document.activeElement.id === 'create_variation_data' || buttonPressed === 'create_variation_data') {
        log.textContent = tak_ali_cre_var_d + (new Date()).toLocaleTimeString();
    } else if (document.activeElement.id === 'submit_save_var' || buttonPressed === 'submit_save_var') {
        log.textContent = tak_ali_cre_var + (new Date()).toLocaleTimeString();
    }
    //console.log(document.getElementById('variation_list').value);
    taknalogycust_submit(this, event, log);
}
var taknalogycust_submit = function (form, event, log) {
    //  console.log(document.activeElement.id);
    //event.preventDefault();
    jQuery.ajax({
        url: ajaxurl,
        type: jQuery(form).attr("method"),
        data: {
            'action': 'tak_ajax_ali_importer',
            'form_data': jQuery(form).serialize(),
            'form_name': jQuery(form).attr('id'),
            'form_btn': document.activeElement.id,
            'cust_form_btn': buttonPressed,
            'variation_list': document.getElementById('variation_list').value
        }
    }).done(function (response) {
        document.getElementById('variation_list').value = '';
        //console.log(response);
        // document.getElementById('product_type_hidden').value = 'simple';
        if (response['success']) {
            if (response['data']['action'] == 'product_created') {
                //console.log(response['data']);
                document.getElementById('created_product').value = response['data']['id'];
                if (response['data'].message.product_type === 'Simple product') {
                    document.getElementById('create_variation_data').style.display = "none";
                    document.getElementById('submit_save_var').style.display = "none";
                    document.getElementById('product_type_hidden').value = 'simple';
                } else if (response['data'].message.product_type === 'Variable product') {
                    document.getElementById('create_variation_data').style.display = "block";
                    document.getElementById('submit_save_var').style.display = "block";
                    document.getElementById('product_type_hidden').value = 'variable';
                } else {

                }
                log.textContent = response['data'].message.product_type + ' ' + response['data'].message.status + ' at ' + (new Date()).toLocaleTimeString();
                document.getElementById('get_base_product_status').innerText = response['data'].message.product_type + ' ' + response['data'].message.status + ' at ' + (new Date()).toLocaleTimeString();
                document.getElementById('get_base_product_status').style.display = "block";
                if (document.getElementById('full_import').checked) {
                    buttonPressed = 'get_images';
                    document.getElementById("get_product_images").dispatchEvent(evt);
                }
            } else if (response['data']['action'] == 'gallery_created' || response['data']['action'] == 'gallery_found' || response['data']['action'] == 'gallery_error') {
                log.textContent = response['data']['message'] + ' at ' + (new Date()).toLocaleTimeString();
                document.getElementById('submit_images_status').innerText = response['data']['message'] + ' at ' + (new Date()).toLocaleTimeString();
                document.getElementById('submit_images_status').style.display = "block";
                if (document.getElementById('full_import').checked) {
                    buttonPressed = 'create_attributes';
                    document.getElementById("get_product_images").dispatchEvent(evt);
                }
            } else if (response['data']['action'] == 'product_attributes_created' || response['data']['action'] == 'product_attributes_failed') {
                log.textContent = response['data']['message'] + ' at ' + (new Date()).toLocaleTimeString();
                document.getElementById('submit_attributes_status').innerText = response['data']['message'] + ' at ' + (new Date()).toLocaleTimeString();
                document.getElementById('submit_attributes_status').style.display = "block";
                if (document.getElementById('full_import').checked && document.getElementById('product_type_hidden').value === 'variable') {
                    buttonPressed = 'create_variation_data';
                    document.getElementById("get_product_images").dispatchEvent(evt);
                }
            } else if (response['data']['action'] == 'product_variations_data_created' || response['data']['action'] == 'product_variations_data_failed') {
                if (response['data']['variation_list'] != '') {
                    document.getElementById('variation_list').value = JSON.stringify(response['data']['variation_list']);
                }
                log.textContent = response['data']['message'] + ' at ' + (new Date()).toLocaleTimeString();
                document.getElementById('submit_var_data_status').innerText = response['data']['message'] + ' at ' + (new Date()).toLocaleTimeString();
                document.getElementById('submit_var_data_status').style.display = "block";
                if (document.getElementById('full_import').checked && document.getElementById('product_type_hidden').value === 'variable') {
                    buttonPressed = 'submit_save_var';
                    document.getElementById("get_product_images").dispatchEvent(evt);
                }
            } else if (response['data']['action'] == 'product_variations_saved' || response['data']['action'] == 'product_variations_failed') {
                log.textContent = response['data']['message'] + ' at ' + (new Date()).toLocaleTimeString();
                document.getElementById('submit_save_var_status').innerText = response['data']['message'] + ' at ' + (new Date()).toLocaleTimeString();
                document.getElementById('submit_save_var_status').style.display = "block";
                buttonPressed = '';
            }
        }
    });
}