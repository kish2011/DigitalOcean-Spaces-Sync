
const dos_loader = jQuery('.dos__loader')
const dos_message = jQuery('.dos__message')
const dos_test_connection = jQuery('.dos__test__connection')

jQuery( function () {

  // check connection button
  dos_test_connection.on( 'click', function () {

    console.log( 'Testing connection to DO Spaces Container' )

    const data = {
      dos_key: jQuery('input[name=dos_key]').val(),
      dos_secret: jQuery('input[name=dos_secret]').val(),
      dos_endpoint: jQuery('input[name=dos_endpoint]').val(),
      dos_container: jQuery('input[name=dos_container]').val(),
      action: 'dos_test_connection'
    }

    dos_loader.hide()

    jQuery.ajax({
      type: 'POST',
      url: ajaxurl,
      data: data,
      dataType: 'html'
    }).done( function ( res ) {
      dos_message.show()
      dos_message.html('<br/>' + res)
      dos_loader.hide()
      jQuery('html,body').animate({ scrollTop: 0 }, 1000)
    })

  })

})