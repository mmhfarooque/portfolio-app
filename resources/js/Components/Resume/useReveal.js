import { onMounted, onBeforeUnmount } from 'vue';

export function prefersReducedMotion() {
    return typeof window !== 'undefined'
        && typeof window.matchMedia === 'function'
        && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

/**
 * Fades [data-reveal] elements inside rootRef in as they scroll into view.
 * SSR, no-JS, print and reduced-motion all get the content fully visible:
 * the hiding class is only added in the browser, after mount.
 */
export function useReveal(rootRef) {
    let observer = null;

    onMounted(() => {
        const root = rootRef.value;
        if (!root || prefersReducedMotion() || !('IntersectionObserver' in window)) {
            return;
        }

        root.classList.add('r-motion');

        observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-in');
                        observer.unobserve(entry.target);
                    }
                });
            },
            { rootMargin: '0px 0px -8% 0px', threshold: 0.15 },
        );

        root.querySelectorAll('[data-reveal]').forEach((el) => observer.observe(el));
    });

    onBeforeUnmount(() => observer?.disconnect());
}
