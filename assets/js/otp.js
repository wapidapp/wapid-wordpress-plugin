(function ($) {
  function getPhoneForContext(context, box) {
    if (context === 'login') {
      return $('#username').val() || '';
    }
    if (context === 'register') {
      return $('#wa_reg_phone').val() || '';
    }
    return $('#billing_phone').val() || '';
  }

  function setFeedback(box, message, isError) {
    var $f = box.find('.wa-otp-feedback');
    $f.text(message || '');
    $f.toggleClass('is-error', !!isError);
    $f.toggleClass('is-success', !isError && !!message);
  }

  $(document).on('click', '.wa-send-otp', function () {
    var box = $(this).closest('.wa-otp-box');
    var context = box.data('wa-context');
    var value = getPhoneForContext(context, box);

    setFeedback(box, wapidAutomationOtp.messages.sending, false);

    var data = {
      action: 'whatsapp_automation_send_otp',
      nonce: wapidAutomationOtp.nonce,
      context: context
    };

    if (context === 'login') {
      data.identifier = value;
    } else {
      data.phone = value;
    }

    $.post(wapidAutomationOtp.ajaxUrl, data)
      .done(function (res) {
        if (!res.success) {
          setFeedback(box, (res.data && res.data.message) || 'OTP send failed', true);
          return;
        }

        box.find('.wa-otp-challenge').val(res.data.challenge_id || '');
        box.find('.wa-otp-token').val('');
        setFeedback(box, wapidAutomationOtp.messages.sent + ' ' + (res.data.masked_phone || ''), false);
      })
      .fail(function (xhr) {
        var msg = (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) || 'OTP send failed';
        setFeedback(box, msg, true);
      });
  });

  $(document).on('click', '.wa-verify-otp', function () {
    var box = $(this).closest('.wa-otp-box');
    var context = box.data('wa-context');
    var challenge = box.find('.wa-otp-challenge').val();
    var otp = box.find('.wa-otp-input').val();

    setFeedback(box, wapidAutomationOtp.messages.verifying, false);

    $.post(wapidAutomationOtp.ajaxUrl, {
      action: 'whatsapp_automation_verify_otp',
      nonce: wapidAutomationOtp.nonce,
      context: context,
      challenge_id: challenge,
      otp: otp
    })
      .done(function (res) {
        if (!res.success) {
          setFeedback(box, (res.data && res.data.message) || 'OTP verification failed', true);
          return;
        }

        box.find('.wa-otp-token').val(res.data.verify_token || '');
        setFeedback(box, wapidAutomationOtp.messages.verified, false);
      })
      .fail(function (xhr) {
        var msg = (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) || 'OTP verification failed';
        setFeedback(box, msg, true);
      });
  });
})(jQuery);
