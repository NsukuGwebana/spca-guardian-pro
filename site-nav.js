/* SPCA Guardian Pro: shared top bar for the public pages (the same as the homepage's).
   Use it by putting this line straight after <body>:
       <script src="site-nav.js"></script>
   It draws the bar where the script tag is, so there's no flicker, and loads its own
   styles (site-nav.css) and icons. Change the menu here and every page follows. */
(function () {
  var here = (location.pathname.split('/').pop() || 'Homepage.html').toLowerCase();

  var LINKS = [
    { href: 'Homepage.html', icon: 'fa-home', text: 'Home' },
    { href: 'Homepage.html#stats', icon: 'fa-chart-line', text: 'Impact', also: ['impact.html'] },
    { href: 'about_us.html', icon: 'fa-info-circle', text: 'About us' },
    { href: 'contact.html', icon: 'fa-envelope', text: 'Contact' }
  ];
  var INVOLVED = [
    { href: 'adoption_guide.html', icon: 'fa-paw', text: 'Adopt a Pet', also: ['adoption_form.php', 'adopt_form.php'] },
    { href: 'track_adoption.php', icon: 'fa-clipboard-list', text: 'Track Adoption Application' },
    { href: 'donation_guide.html', icon: 'fa-hand-holding-usd', text: 'Donate', also: ['adddonations2.html', 'pay.html'] },
    { href: 'vol.html', icon: 'fa-hands-helping', text: 'Volunteer', also: ['community.html', 'learnmore.html'] },
    { href: 'track_volunteer.php', icon: 'fa-clipboard-check', text: 'Track Volunteer' },
    { href: 'foster_guide.html', icon: 'fa-home', text: 'Foster', also: ['foster.php', 'foster_form.php'] },
    { href: 'track_foster.php', icon: 'fa-clipboard-check', text: 'Track Foster Application' },
    { href: 'report_guide.html', icon: 'fa-exclamation-circle', text: 'Report Animal Cruelty', also: ['cruelty_report_form.php'] },
    { href: 'WaysToHelp.html', icon: 'fa-hand-holding-heart', text: 'Ways to Help' }
  ];

  function isHere(l) {
    var file = l.href.split('#')[0].toLowerCase();
    if (l.href.indexOf('#') > -1 && file === here) return false; // "Impact" on the homepage is a section
    return file === here || (l.also || []).indexOf(here) > -1;
  }
  function esc(s) { return String(s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }
  function link(l) {
    return '<a href="' + esc(l.href) + '"' + (isHere(l) ? ' aria-current="page"' : '') + '><i class="fas ' + l.icon + '" aria-hidden="true"></i> ' + esc(l.text) + '</a>';
  }

  // Styles and icons (added once, even if a page already has them)
  function addCss(href, test) {
    if (test && document.querySelector(test)) return;
    var el = document.createElement('link');
    el.rel = 'stylesheet'; el.href = href;
    document.head.appendChild(el);
  }
  addCss('site-nav.css', 'link[href$="site-nav.css"]');
  addCss('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', 'link[href*="font-awesome"]');

  var html =
    '<div class="spca-sitenav" role="navigation" aria-label="Main">' +
      '<a class="sn-brand" href="Homepage.html">' +
        '<img class="sn-logo" src="Images/hug.PNG" alt="SPCA Guardian Pro logo">' +
        '<span class="sn-name"><strong>SPCA GUARDIAN PRO</strong><span>A Home For Every Animal</span></span>' +
      '</a>' +
      '<button class="sn-burger" type="button" aria-expanded="false" aria-label="Menu"><i class="fas fa-bars" aria-hidden="true"></i></button>' +
      '<div class="sn-right">' +
        '<ul class="sn-links">' +
          LINKS.map(function (l) { return '<li>' + link(l) + '</li>'; }).join('') +
          '<li class="sn-drop">' +
            '<button type="button" aria-expanded="false"' + (INVOLVED.some(isHere) ? ' style="color:#60a5fa"' : '') + '>' +
              '<i class="fas fa-hand-holding-heart" aria-hidden="true"></i> Get Involved <i class="fas fa-chevron-down sn-chev" aria-hidden="true"></i>' +
            '</button>' +
            '<ul class="sn-menu">' + INVOLVED.map(function (l) { return '<li>' + link(l) + '</li>'; }).join('') + '</ul>' +
          '</li>' +
        '</ul>' +
        '<a class="sn-login" href="Newlogin.html">LOGIN</a>' +
        '<form class="sn-search" role="search">' +
          '<input type="search" placeholder="Search..." aria-label="Search this page">' +
          '<button type="submit" aria-label="Search"><i class="fas fa-search" aria-hidden="true"></i></button>' +
        '</form>' +
      '</div>' +
    '</div>';

  var me = document.currentScript;
  if (me && me.parentNode) me.insertAdjacentHTML('afterend', html);
  else document.body.insertAdjacentHTML('afterbegin', html);

  var bar = document.querySelector('.spca-sitenav');
  var drop = bar.querySelector('.sn-drop');
  var dropBtn = drop.querySelector('button');
  var burger = bar.querySelector('.sn-burger');

  // Get Involved opens on hover (desktop) and on tap/click (phones, keyboards)
  dropBtn.addEventListener('click', function (e) {
    e.stopPropagation();
    var open = drop.classList.toggle('open');
    dropBtn.setAttribute('aria-expanded', open);
  });
  document.addEventListener('click', function (e) {
    if (!drop.contains(e.target)) { drop.classList.remove('open'); dropBtn.setAttribute('aria-expanded', 'false'); }
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') { drop.classList.remove('open'); dropBtn.setAttribute('aria-expanded', 'false'); }
  });
  burger.addEventListener('click', function () {
    var open = bar.classList.toggle('open');
    burger.setAttribute('aria-expanded', open);
  });

  // Search: finds the words on this page, highlights them and scrolls to the first one
  var form = bar.querySelector('.sn-search');
  var msg = null, timer = null;
  function say(text) {
    if (!msg) { msg = document.createElement('div'); msg.className = 'sn-search-msg'; msg.setAttribute('role', 'status'); form.appendChild(msg); }
    msg.textContent = text; msg.hidden = false;
    clearTimeout(timer); timer = setTimeout(function () { msg.hidden = true; }, 3500);
  }
  function clearMarks() {
    document.querySelectorAll('mark.spca-find').forEach(function (m) {
      m.parentNode.replaceChild(document.createTextNode(m.textContent), m);
      m.parentNode && m.parentNode.normalize && m.parentNode.normalize();
    });
  }
  form.addEventListener('submit', function (e) {
    e.preventDefault();
    clearMarks();
    var q = form.querySelector('input').value.trim();
    if (q.length < 2) { say('Type at least 2 letters.'); return; }
    var walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT, {
      acceptNode: function (n) {
        var p = n.parentNode;
        if (!p || bar.contains(p) || /^(SCRIPT|STYLE|NOSCRIPT|TEXTAREA|OPTION)$/.test(p.nodeName)) return NodeFilter.FILTER_REJECT;
        if (!p.offsetParent && getComputedStyle(p).position !== 'fixed') return NodeFilter.FILTER_REJECT; // hidden text
        return n.nodeValue.toLowerCase().indexOf(q.toLowerCase()) > -1 ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_SKIP;
      }
    });
    var nodes = [], n;
    while ((n = walker.nextNode()) && nodes.length < 200) nodes.push(n);
    nodes.forEach(function (node) {
      var i = node.nodeValue.toLowerCase().indexOf(q.toLowerCase());
      var hit = node.splitText(i); hit.splitText(q.length);
      var mark = document.createElement('mark'); mark.className = 'spca-find';
      hit.parentNode.replaceChild(mark, hit); mark.appendChild(hit);
    });
    var first = document.querySelector('mark.spca-find');
    if (first) {
      first.scrollIntoView({ behavior: 'smooth', block: 'center' });
      say(nodes.length + (nodes.length === 1 ? ' match' : ' matches') + ' on this page');
    } else {
      say('No matches on this page. Try the menu above.');
    }
  });
})();
