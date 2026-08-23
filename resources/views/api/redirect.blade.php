{{-- @extends('layout.main.header') --}}
{{-- @section('content') --}}

<script>
  document.addEventListener("DOMContentLoaded", function(event) {
    var links = document.querySelectorAll('a');
    var baseUri = 'exp://wg-qka.notbrent.app.exp.direct';

    // Take the uri from the params
    var qs = decodeURIComponent(document.location.search);
    console.log(qs,"***");
    if (qs) {
      baseUri = qs.split('?linkingUri=')[1];
    }

    // Update the link urls
    for (var i = 0; i < links.length; ++i) {
      links[i].href = links[i].href.replace('exp://REPLACE_ME/', baseUri);
      console.log(links[i].href, baseUri);
    }

    var redirectInterval = setInterval(function() {
      var countdown = document.querySelector('.countdown');
      var t = parseInt(countdown.innerText, 10);
      t -= 1;

      countdown.innerText = t;

      if (t === 0) {
        clearInterval(redirectInterval);
        window.location.href = baseUri;
      }
    }, 1000);
  });
</script>

<h2>بازگشت به اپلیکیشن </h2>
<ul style="margin-left: 0; padding-left: 15px;">
  <li>
    <a href="exp://REPLACE_ME/">
      برای بازگشت کلیک کنید
    </a>
  </li>
</ul>

<p>
  پس <span class="countdown">5</span> ثانیه به اپلیکیشن باز خواهید گشت.
</p>

{{-- @endsection --}}