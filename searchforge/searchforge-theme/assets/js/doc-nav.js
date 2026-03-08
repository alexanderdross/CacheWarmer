/**
 * Doc sidebar scroll-spy: highlights the active section link.
 */
(function () {
	const links = document.querySelectorAll('.sf-doc-nav__link');
	if (!links.length) return;

	const sections = [];
	links.forEach(function (link) {
		const id = link.getAttribute('href').slice(1);
		const el = document.getElementById(id);
		if (el) sections.push({ el: el, link: link });
	});

	function update() {
		let current = sections[0];
		for (let i = 0; i < sections.length; i++) {
			if (sections[i].el.getBoundingClientRect().top <= 100) {
				current = sections[i];
			}
		}
		links.forEach(function (l) { l.classList.remove('active'); });
		if (current) current.link.classList.add('active');
	}

	window.addEventListener('scroll', update, { passive: true });
	update();
})();
