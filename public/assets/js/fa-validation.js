/*
 * پیغام‌های اعتبارسنجی مرورگر به فارسی (به‌جای «Please fill in this field.» و مشابه آن).
 * روی همه‌ی فرم‌های سایت کار می‌کند. نام فیلد در پیغام به ترتیب از data-label، aria-label،
 * placeholder یا <label> فیلد خوانده می‌شود. برای پیغام اختصاصی الگو (pattern) از data-msg-pattern استفاده کنید.
 */
(function () {
  'use strict';

  var faDigits = function (n) {
    return String(n).replace(/\d/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'[d]; });
  };

  function fieldLabel(el) {
    var text = el.getAttribute('data-label') || el.getAttribute('aria-label') || el.getAttribute('placeholder');
    if (!text && el.id) {
      var label = document.querySelector('label[for="' + el.id + '"]');
      if (label) text = label.textContent;
    }
    text = (text || '').replace(/[*:：\s]+$/g, '').replace(/\s+/g, ' ').trim();
    // placeholderهایی مثل «مثلاً 09123456789» یا متن‌های بلند برچسب مناسبی نیستند
    if (!text || text.length > 40 || /^(مثل|مثلا|مثلاً|e\.g)/i.test(text) || /^[\d\s+\-()@.]+$/.test(text)) return '';
    return text;
  }

  function message(el) {
    var v = el.validity;
    var type = (el.type || '').toLowerCase();
    var label = fieldLabel(el);

    if (v.valueMissing) {
      if (type === 'checkbox') return 'لطفاً این گزینه را تأیید نمایید.';
      if (type === 'radio' || el.tagName === 'SELECT') {
        return label ? 'لطفاً «' + label + '» را انتخاب نمایید.' : 'لطفاً یکی از گزینه‌ها را انتخاب نمایید.';
      }
      if (type === 'file') return 'لطفاً فایل را انتخاب نمایید.';
      return label ? 'لطفاً ' + label + ' را وارد نمایید.' : 'لطفاً این فیلد را تکمیل نمایید.';
    }
    if (v.typeMismatch) {
      if (type === 'email') return 'لطفاً یک آدرس ایمیل معتبر وارد نمایید.';
      if (type === 'url') return 'لطفاً یک آدرس اینترنتی معتبر وارد نمایید.';
      return 'مقدار وارد‌شده معتبر نیست.';
    }
    if (v.patternMismatch) {
      return el.getAttribute('data-msg-pattern')
        || (label ? 'قالب ' + label + ' صحیح نیست.' : 'قالب مقدار وارد‌شده صحیح نیست.');
    }
    if (v.tooShort) return 'لطفاً حداقل ' + faDigits(el.minLength) + ' کاراکتر وارد نمایید.';
    if (v.tooLong) return 'حداکثر ' + faDigits(el.maxLength) + ' کاراکتر مجاز است.';
    if (v.rangeUnderflow) return 'مقدار باید حداقل ' + faDigits(el.min) + ' باشد.';
    if (v.rangeOverflow) return 'مقدار باید حداکثر ' + faDigits(el.max) + ' باشد.';
    if (v.stepMismatch) return 'لطفاً یک مقدار معتبر وارد نمایید.';
    if (v.badInput) return type === 'number' ? 'لطفاً فقط عدد وارد نمایید.' : 'مقدار وارد‌شده معتبر نیست.';
    return '';
  }

  // رویداد invalid حباب نمی‌زند، پس در فاز capture گرفته می‌شود
  document.addEventListener('invalid', function (e) {
    var el = e.target;
    if (!el || typeof el.setCustomValidity !== 'function') return;
    el.setCustomValidity('');
    if (!el.validity.valid) el.setCustomValidity(message(el));
  }, true);

  // با تغییر مقدار، پیغام قبلی پاک می‌شود تا مرورگر دوباره بررسی کند
  function reset(e) {
    var el = e.target;
    if (!el || typeof el.setCustomValidity !== 'function') return;
    el.setCustomValidity('');
    // برای گروه رادیو، پیغام بقیه‌ی گزینه‌های هم‌نام هم پاک شود
    if (el.type === 'radio' && el.name && el.form) {
      Array.prototype.forEach.call(el.form.querySelectorAll('input[type="radio"]'), function (r) {
        if (r.name === el.name) r.setCustomValidity('');
      });
    }
  }
  document.addEventListener('input', reset, true);
  document.addEventListener('change', reset, true);
})();
