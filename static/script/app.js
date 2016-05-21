/*
send form data via ajax and return the data to callback function 
*/
function send_form( name , func )
{
	var url = $('#'+name).attr('action');
	
	var params = {};
	$.each( $('#'+name).serializeArray(), function(index,value) 
	{
		params[value.name] = value.value;
	});
	
	
	$.post( url , params , func );	
}

/*
send form data via ajax and show the return content to pop div 
*/

function send_form_pop( name )
{
	return send_form( name , function( data ){ show_pop_box( data ); } );
}

/*
send form data via ajax and show the return content in front of the form 
*/
function send_form_in( name )
{	
	return send_form( name , function( data ){ set_form_notice( name , data ) } );
}


function set_form_notice( name , data )
{
	if( $('#form_'+name+'_notice').length != 0 )
	{
		$('#form_'+name+'_notice').html(data);
		//$('#form_'+name+'_notice').show();
	}
	else
	{
		var odiv = $( "<div class='form_notice'></div>" );
		odiv.attr( 'id' , 'form_'+name+'_notice' );
		odiv.html(data);
		$('#'+name).prepend( odiv );
	}
	//$('#form_'+name+'_notice').slideUp(2500);
}


function show_pop_box( data , popid )
{
	if( popid == undefined ) popid = 'lp_pop_box'
	//console.log($('#' + popid) );
	if( $('#' + popid).length == 0 )
	{
		var did = $('<div><div id="' + 'lp_pop_container' + '"></div></div>');
		did.attr( 'id' , popid );
		did.css( 'display','none' );
		$('body').prepend(did);
	} 
	
	if( data != '' )
		$('#lp_pop_container').html(data);
	
	var left = ($(window).width() - $('#' + popid ).width())/2;
	
	$('#' + popid ).css('left',left);
	$('#' + popid ).css('display','block');
}

function hide_pop_box( popid )
{
	if( popid == undefined ) popid = 'lp_pop_box'
	$('#' + popid ).css('display','none');
}



/* post demo
$.post( 'url&get var'  , { 'post':'value'} , function( data )
{
	var data_obj = jQuery.parseJSON( data );
	console.log( data_obj  );
	
	if( data_obj.err_code == 0  )
	{
					
	}
	else
	{
		
	}	
} );

*/

if (!this.console) {
	this.console = {
		log: function () {}
	};
}

/**
 * 过虑掉恶心的sae认证js
 */
function filterJSScript(data) {
	return data.replace(/\<script.*/, '');
}

var loginErrorCount = 0;
var registerErrorCount = 0;
$(function(){
	$('a[rel*=facebox]').facebox({
        loadingImage : STATIC_HOST+'/static/image/loading.gif',
        closeImage   : STATIC_HOST+'/static/image/closelabel.png'
    });

    //回车事件
    document.onkeydown = function(e){
	    var ev = document.all ? window.event : e;
	    if(ev.keyCode==13) {
	    	if($('#facebox').is(':visible')) {
	    		$('#signIn').click();
	    	}
	     }
	}

    $(document).on('click', '#signIn', function(){
    	var name = $('#inputName').val();
    	var pwd = $('#inputPwd').val();
    	if(name == '' || pwd == '') {
    		if(loginErrorCount >= 3) {
    			alert('错 '+loginErrorCount+' 次了，我也是...');
    		} else {
    			alert('请输入正确的用户名或密码');
    		}
    		++loginErrorCount;
    		return false;
    	}

    	$.post(
    		HTTP_HOST + '/?a=login',
    		{'name':name, 'passwd': pwd},
    		function(data){
    			data = $.parseJSON(filterJSScript(data));
    			if(data.status) {
    				window.location.reload(true);
    			} else {
    				alert(data.msg);
    			}
    		}
    	);
    });

    $(document).on('click', '#signUp', function(){
    	var name = $('#inputName').val();
    	var pwd  = $('#inputPwd').val();
    	var code = $('#inputCode').val();

    	if(name == '' || pwd == '' || code == '') {
    		if(registerErrorCount >= 3) {
    			alert('错 '+registerErrorCount+' 次了，我也是...');
    		} else {
    			alert('请输入正确的注册信息');
    		}
    		++registerErrorCount;
    		return false;
    	}

    	$.post(
    		HTTP_HOST + '/?a=register',
    		{'name':name, 'passwd': pwd, 'code': code},
    		function(data){
    			data = $.parseJSON(filterJSScript(data));
    			alert(data.msg);
    			if(data.status) {
    				window.location.reload(true);
    			} 
    		}
    	);
    });

    $('#selEat').click(function() {
    	var eatEle = $('#eatTable');
    	if(!eatEle.is(':visible')) {
    		eatEle.show();
    	} else {
    		eatEle.hide();
    	}
    	
    });

    $('#eatSelOk').click(function() {
    	send_form_in('eatForm');
    });

    $('#doPay').click(function() {
    	var payAmount = $('#payAmount').val();
        if(payAmount == '') {
        	alert('请输入正确的金额');
        	return false;
        }
    	$.post(
    		HTTP_HOST + '/?a=recharge',
    		{'payAmount':payAmount},
    		function(data){
    			data = $.parseJSON(filterJSScript(data));
    			if(!data.status) {
    				alert(data.msg);
    			} else {
    				jQuery.facebox({ image: STATIC_HOST+'/static/image/pay.jpg' });
    			}
    		}
    	);
    });

    $('.balanceBar').click(function() {
    	if($(this).hasClass('active')) {
    		return false;
    	}

    	$(this).addClass('active').siblings('li').removeClass('active');

    	var attrId = $(this).attr('id');
    	$('.balanceInfoDiv').addClass('hide');
    	$('.'+attrId).removeClass('hide');
    });

    $('.balanceAct').click( function() {
        var ele = $(this);
    	var id = ele.data('id');
    	$.post(
    		HTTP_HOST + '/?c=fun&a=balanceAct',
    		{'id':id},
    		function(data){
    			data = $.parseJSON(filterJSScript(data));
    			alert(data.msg);
                ele.remove();
    		}
    	);
    });

    $('.setMenu').click( function() {
        var ele = $(this);
        var id = ele.data('id');
        $.post(
            HTTP_HOST + '/?c=fun&a=setMenu',
            {'id':id},
            function(data){
                data = $.parseJSON(filterJSScript(data));
                alert(data.msg);
            }
        );
    });

    $('.ajaxForm').click( function(){
        var formId = $(this).closest('form').attr('id');
        send_form(formId, function(data){
            data = $.parseJSON(filterJSScript(data));
            alert(data.msg);
        });
    });
});
