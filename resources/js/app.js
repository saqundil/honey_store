import './bootstrap';
import { Chart, registerables } from 'chart.js';
import { gsap } from 'gsap';
import { MotionPathPlugin } from 'gsap/MotionPathPlugin';

Chart.register(...registerables);
window.Chart = Chart;

gsap.registerPlugin(MotionPathPlugin);

const initHeroBeeSwoop = () => {
	if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
		return;
	}

	const getVisibleElement = (selector) => {
		return Array.from(document.querySelectorAll(selector)).find((element) => {
			return element.getClientRects().length > 0;
		});
	};

	const bee = getVisibleElement('[data-hero-bee-swoop]');
	const sprite = getVisibleElement('[data-hero-bee-sprite]');
	const pathDown = getVisibleElement('[data-hero-bee-path-down]');
	const pathUp = getVisibleElement('[data-hero-bee-path-up]');

	if (!bee || !sprite || !pathDown || !pathUp) {
		return;
	}

	let timeline;
	let wingTween;

	const buildTimeline = () => {
		timeline?.kill();
		wingTween?.kill();

		const isMobile = window.matchMedia('(max-width: 1023px)').matches;
		const forwardDuration = isMobile ? 1.2 : 1.8;
		const returnDuration = isMobile ? 2.1 : 1.7;
		const mobilePause = 0.7;
		const mobilePausePoint = 0.52;

		gsap.set(bee, {
			xPercent: -50,
			yPercent: -50,
			transformOrigin: '50% 50%',
		});

		timeline = gsap.timeline({
			defaults: {
				ease: 'power1.inOut',
			},
			repeat: -1,
		});

		if (isMobile) {
			timeline
				.to(bee, {
					duration: forwardDuration * 0.62,
					motionPath: {
						path: pathDown,
						align: pathDown,
						alignOrigin: [0.5, 0.5],
						autoRotate: 18,
						start: 0,
						end: mobilePausePoint,
					},
				})
				.to({}, { duration: mobilePause })
				.to(bee, {
					duration: forwardDuration * 0.38,
					motionPath: {
						path: pathDown,
						align: pathDown,
						alignOrigin: [0.5, 0.5],
						autoRotate: 18,
						start: mobilePausePoint,
						end: 1,
					},
				})
				.to(bee, {
					duration: returnDuration,
					ease: 'power2.inOut',
					motionPath: {
						path: pathUp,
						align: pathUp,
						alignOrigin: [0.5, 0.5],
						autoRotate: -18,
						start: 0,
						end: 1,
					},
				});
		} else {
			timeline
				.to(bee, {
					duration: forwardDuration,
					motionPath: {
						path: pathDown,
						align: pathDown,
						alignOrigin: [0.5, 0.5],
						autoRotate: 18,
						start: 0,
						end: 1,
					},
				})
				.to(bee, {
					duration: returnDuration,
					motionPath: {
						path: pathUp,
						align: pathUp,
						alignOrigin: [0.5, 0.5],
						autoRotate: 12,
						start: 0,
						end: 1,
					},
				});
		}

		wingTween = gsap.to(sprite, {
			rotation: 5,
			x: 0.8,
			y: -0.8,
			duration: 0.09,
			ease: 'power1.inOut',
			repeat: -1,
			yoyo: true,
			transformOrigin: '50% 50%',
		});
	};

	buildTimeline();

	let resizeTimer;

	window.addEventListener('resize', () => {
		window.clearTimeout(resizeTimer);
		resizeTimer = window.setTimeout(buildTimeline, 120);
	});
};

initHeroBeeSwoop();
