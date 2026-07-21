/**
 * Fond ambient cinématique (blobs lumineux fluides, style motion design).
 */
export function initAmbientBackground() {
    const canvas = document.querySelector('[data-ambient-canvas]');
    if (!canvas) return;

    const ctx = canvas.getContext('2d', { alpha: true });
    if (!ctx) return;

    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');

    const blobs = [
        { x: 0.18, y: 0.12, r: 0.45, h: 0, s: 88, l: 48, a: 0.72, sp: 0.00013, ph: 0 },
        { x: 0.82, y: 0.38, r: 0.38, h: 355, s: 75, l: 42, a: 0.58, sp: 0.00011, ph: 1.4 },
        { x: 0.48, y: 0.78, r: 0.4, h: 12, s: 82, l: 40, a: 0.55, sp: 0.00012, ph: 2.6 },
        { x: 0.62, y: 0.58, r: 0.26, h: 285, s: 55, l: 38, a: 0.35, sp: 0.00015, ph: 3.8 },
        { x: 0.28, y: 0.52, r: 0.22, h: 38, s: 90, l: 52, a: 0.28, sp: 0.00017, ph: 5.1 },
    ];

    let width = 0;
    let height = 0;
    let dpr = 1;
    let time = 0;
    let raf = 0;
    let running = true;

    const resize = () => {
        dpr = Math.min(window.devicePixelRatio || 1, 2);
        width = window.innerWidth;
        height = window.innerHeight;
        canvas.width = Math.floor(width * dpr);
        canvas.height = Math.floor(height * dpr);
        canvas.style.width = `${width}px`;
        canvas.style.height = `${height}px`;
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    };

    const paint = () => {
        ctx.clearRect(0, 0, width, height);

        const base = ctx.createLinearGradient(0, 0, 0, height);
        base.addColorStop(0, '#0c0610');
        base.addColorStop(0.45, '#08080c');
        base.addColorStop(1, '#030305');
        ctx.fillStyle = base;
        ctx.fillRect(0, 0, width, height);

        ctx.globalCompositeOperation = 'screen';

        for (const b of blobs) {
            const t = time * b.sp + b.ph;
            const cx = (b.x + Math.sin(t * 1.25) * 0.14 + Math.cos(t * 0.7) * 0.06) * width;
            const cy = (b.y + Math.cos(t * 1.15) * 0.12 + Math.sin(t * 0.85) * 0.05) * height;
            const pulse = 1 + Math.sin(t * 0.9) * 0.1;
            const radius = b.r * Math.min(width, height) * pulse;

            const g = ctx.createRadialGradient(cx, cy, 0, cx, cy, radius);
            g.addColorStop(0, `hsla(${b.h}, ${b.s}%, ${b.l + 8}%, ${b.a})`);
            g.addColorStop(0.35, `hsla(${b.h}, ${b.s}%, ${b.l}%, ${b.a * 0.45})`);
            g.addColorStop(0.72, `hsla(${b.h}, ${b.s - 10}%, ${b.l - 8}%, ${b.a * 0.12})`);
            g.addColorStop(1, 'hsla(0, 0%, 0%, 0)');

            ctx.fillStyle = g;
            ctx.beginPath();
            ctx.arc(cx, cy, radius, 0, Math.PI * 2);
            ctx.fill();
        }

        ctx.globalCompositeOperation = 'source-over';

        const sweep = ctx.createLinearGradient(-width * 0.2, 0, width * 1.2, height);
        const sweepT = (Math.sin(time * 0.00008) + 1) * 0.5;
        sweep.addColorStop(Math.max(0, sweepT - 0.18), 'rgba(255, 220, 200, 0)');
        sweep.addColorStop(sweepT, 'rgba(255, 120, 90, 0.06)');
        sweep.addColorStop(Math.min(1, sweepT + 0.12), 'rgba(255, 220, 200, 0)');
        ctx.fillStyle = sweep;
        ctx.fillRect(0, 0, width, height);
    };

    const loop = () => {
        if (!running) return;
        time += 16;
        paint();
        raf = requestAnimationFrame(loop);
    };

    const drawOnce = () => {
        time = 4000;
        paint();
    };

    resize();
    window.addEventListener('resize', resize, { passive: true });

    if (reduced.matches) {
        drawOnce();
        return;
    }

    const onMotionChange = (e) => {
        if (e.matches) {
            running = false;
            cancelAnimationFrame(raf);
            drawOnce();
        } else if (!running) {
            running = true;
            loop();
        }
    };
    reduced.addEventListener('change', onMotionChange);

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            running = false;
            cancelAnimationFrame(raf);
        } else if (!reduced.matches) {
            running = true;
            loop();
        }
    });

    loop();
}
