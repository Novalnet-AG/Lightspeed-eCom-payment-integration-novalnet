/**
 * Novalnet payment module
 *
 * This module is used for real time processing of Novalnet transaction of customers.
 *
 * This free contribution made by request.
 * If you have found this script useful a small
 * recommendation as well as a comment on merchant form
 * would be greatly appreciated.
 *
 * @author    Novalnet AG
 * @copyright Copyright by Novalnet
 * @license   https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 *
 * Script: backend.js
 */
 
$(document).ready(function () {
    if($('#success_msg').length) {
        jQuery.notify($('#success_msg').val(), "success");
    }
    if($('#error_message').length) {
        jQuery.notify($('#error_message').val());
    }
    $('[data-toggle="tooltip"]').tooltip();
    $('.nn_link, .nn_admin_link').on('click',function() {
        var admin_portal = 'https://admin.novalnet.de/';
        window.open(admin_portal, '_blank');
    });
    var nn_tariffid;
    
    if (undefined != $('#api_key').val()  && '' != $('#api_key').val()) {
        nn_tariffid = $('#tariff_val').val();
        sendAutoConfigRequest();
    }
    
    $('#api_key').change(function () {
        $('#tariff_val').val('');
        sendAutoConfigRequest();
    });

    $( "#lightspeed" ).submit(function( event ) {
        screenLoader_Global();
        setTimeout(function() {
          remove_screenLoader_Global();
        }, 4000);
    });
    $('#loadingGif').css('display', 'block');
    if($('#payment_action').val()  == 'capture') {
        $("#manual_check_limit").parent('div').parent('div').css('display', 'none');
    }
    if($("#novalnet_sepa_enable_guarantee").is(':not(:checked)')) {
            $("#novalnet_sepa_guarantee_minimum_amount").parent('div').parent('div').css('display', 'none');
            $("#novalnet_sepa_enable_force_guarantee").parent('div').parent('div').css('display', 'none');
    }
    
    $("#novalnet_sepa_enable_guarantee").change(function() {

        if($("#novalnet_sepa_enable_guarantee").is(':not(:checked)')) {
            $("#novalnet_sepa_guarantee_minimum_amount").parent('div').parent('div').css('display', 'none');
            $("#novalnet_sepa_enable_force_guarantee").parent('div').parent('div').css('display', 'none');
        } else {
           $("#novalnet_sepa_guarantee_minimum_amount").parent('div').parent('div').css('display', 'block');
            $("#novalnet_sepa_enable_force_guarantee").parent('div').parent('div').css('display', 'block');
        }
    });
    
    if($("#novalnet_invoice_enable_guarantee").is(':not(:checked)')) {
            $("#novalnet_invoice_guarantee_minimum_amount").parent('div').parent('div').css('display', 'none');
            $("#novalnet_invoice_enable_force_guarantee").parent('div').parent('div').css('display', 'none');
    }
    
    $("#novalnet_invoice_enable_guarantee").change(function() {

        if($("#novalnet_invoice_enable_guarantee").is(':not(:checked)')) {
            $("#novalnet_invoice_guarantee_minimum_amount").parent('div').parent('div').css('display', 'none');
            $("#novalnet_invoice_enable_force_guarantee").parent('div').parent('div').css('display', 'none');
        } else {
           $("#novalnet_invoice_guarantee_minimum_amount").parent('div').parent('div').css('display', 'block');
            $("#novalnet_invoice_enable_force_guarantee").parent('div').parent('div').css('display', 'block');
        }
    });
    
    
    
    
    $('.panel-collapse').on('show.bs.collapse', function () {
        $(this).siblings('.box-header').addClass('active');
    });
    $('.panel-collapse').on('hide.bs.collapse', function () {
        $(this).siblings('.box-header').removeClass('active');
    });
    $("#payment_action").change(function() {
        var state = this.value;
        if (state == "capture") {
            $("#manual_check_limit").parent('div').parent('div').css('display', 'none');
        }else{
            $("#manual_check_limit").parent('div').parent('div').css('display', 'block');
        }
    });

    
    $('.updates').click(function() {
        $('.box-footer').css('display', 'none');
    });
    $('.payment_cfg, .status, .callback, .global_cfg, .payments_cfg').click(function() {
        $('.box-footer').css('display', 'block');
    });
    $('#nn_vertical_tab').find('li').click(function() {
        if($(this).hasClass('active')){
            $(this).removeClass('active');
        }
    });
    $(function () {
      $('.nntool').tooltip()
    })
});

function sendAutoConfigRequest() {
    var product_activation_key  = $('#api_key').val().trim();
    if (!product_activation_key) {
        $('#vendor, #auth_code, #product, #access_key').attr('value', '');
        $('select[name="tariff"]').empty();
        return false;
    }
    var params = {
        'api_config_hash': product_activation_key,
        'lang': 'en',
    };
    if ('XDomainRequest' in window && window.XDomainRequest !== null) {
        // Use Microsoft XDR
        var xdr = new XDomainRequest();
        var query = $.param(params);
        xdr.open('POST', 'https://lightspeed.novalnet.de/backend/api');
        xdr.onload = function () {

            // Autofill the vendor details in the configuration fields
            autofillMerchantDetails(this.responseText);
        };
        xdr.onerror = function () {
            return false;
        };
        xdr.send(query);
    } else {
        $.ajax(
            {
                url: 'https://lightspeed.novalnet.de/backend/api',
                type: 'post',
                dataType: 'html',
                data: params,
                global: false,
                async: false,
                success: function (result) {
                    autofillMerchantDetails(result); // Calling the function to autofill the vendor details in the configuration fields
                }, error: function () {
                    return false;
                }
            }
        );
    }
}

function autofillMerchantDetails(datas)
{
    var nn_tariffid = (undefined != $('#tariff_val').val()) ? $('#tariff_val').val() : undefined;
    var fill_params = $.parseJSON(datas);
    if (undefined != fill_params.config_result) {
        alert(fill_params.config_result);
        $('#vendor, #auth_code, #product, #access_key').attr('value', '');
        $('select[name="tariff"]').empty();
        return false;
    }
    $('#vendor, #auth_code, #product, #access_key').attr('value', '');
    $('select[name="tariff"]').empty();
    for ( var tariff_id in fill_params.tariff) {
        var response_tariff_name  = fill_params.tariff[tariff_id]['name'];
        var response_tariff_value = tariff_id;
        var response_tariff_type  = fill_params.tariff[tariff_id]['type'];
        $('select[name="tariff"]').append($('<option>', {
            value: response_tariff_type+'-'+response_tariff_value,
            text : response_tariff_name
        }));
    }
    if (undefined != nn_tariffid && '' != nn_tariffid) {
        $('#tariff').val(nn_tariffid).attr("selected", "selected");
    } else {
        $('#tariff').val($.trim(fill_params.tariff[(Object.keys(fill_params.tariff)[0])]['type']) + '-' + $.trim(Object.keys(fill_params.tariff)[0])).attr("selected", "selected");
    }
    $('#vendor').val(fill_params.vendor);
    $('#auth_code').val(fill_params.auth_code);
    $('#product').val(fill_params.product);
    $('#access_key').val(fill_params.access_key);
    $('#tariff_val').val($('#tariff').val());
    $('#tariff').val($('#tariff').val());
}


function screenLoader_Global() {
    $('#loader-mask').css('display', 'block');
    $('#loader').css('display', 'block');
}

function remove_screenLoader_Global() {
    $('#loader-mask').css('display', 'none');
    $('#loader').css('display', 'none');
}
