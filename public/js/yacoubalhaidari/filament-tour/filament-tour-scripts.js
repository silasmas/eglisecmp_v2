// Shepherd is loaded globally from the CDN script in the Blade view
const Shepherd = window.Shepherd;

let activeTourInstance = null;

/**
 * Tag sidebar items with data-tour (by URL path, then by navigation label).
 */
function applyTourNavAttributes() {
    const navigationMap = window.navigationMap || {};
    const dynamicSteps = window.dynamicTourSteps || [];

    // 1) Match by resource/page URL (most reliable with Livewire SPA)
    dynamicSteps.forEach((step) => {
        if (!step.id || !step.url) {
            return;
        }

        let stepPath;

        try {
            stepPath = new URL(step.url, window.location.origin).pathname;
        } catch (e) {
            return;
        }

        document.querySelectorAll('a.fi-sidebar-item-btn[href], .fi-sidebar-item a[href]').forEach((link) => {
            let linkPath;

            try {
                linkPath = new URL(link.href, window.location.origin).pathname;
            } catch (e) {
                return;
            }

            if (linkPath !== stepPath) {
                return;
            }

            const item = link.closest('.fi-sidebar-item') || link;

            item.setAttribute('data-tour', step.id);
            link.setAttribute('data-tour', step.id);
        });
    });

    // 2) Fallback: match sidebar label text
    document.querySelectorAll('.fi-sidebar-item, .fi-sidebar-group').forEach((item) => {
        const text = item.textContent.trim();
        const link = item.querySelector('a.fi-sidebar-item-btn, a[href]') || item;

        Object.entries(navigationMap).forEach(([stepId, navLabel]) => {
            if (text.includes(navLabel) || text === navLabel) {
                const sidebarItem = link.closest('.fi-sidebar-item') || item;

                sidebarItem.setAttribute('data-tour', stepId);
                link.setAttribute('data-tour', stepId);
            }
        });
    });
}

/** @deprecated alias */
function autoDetectNavigationElements() {
    applyTourNavAttributes();
}

/**
 * Resolve the sidebar DOM node to highlight for a step.
 */
function resolveTourTargetElement(stepId, attachToSelector = null) {
    applyTourNavAttributes();

    const selector = attachToSelector || `[data-tour="${stepId}"]`;
    const el = document.querySelector(selector);

    if (!el) {
        return null;
    }

    return (
        el.closest('.fi-sidebar-item') ||
        el.closest('.fi-sidebar-group-item') ||
        el.closest('.fi-sidebar-item-button') ||
        el.closest('li') ||
        el
    );
}

function waitForTourTarget(stepId, attachToSelector = null, maxAttempts = 50, intervalMs = 100) {
    return new Promise((resolve) => {
        let attempts = 0;

        const tick = () => {
            const target = resolveTourTargetElement(stepId, attachToSelector);

            if (target) {
                resolve(target);
                return;
            }

            attempts += 1;

            if (attempts >= maxAttempts) {
                console.warn(`Tour target not found for step "${stepId}" after ${maxAttempts} attempts`);
                resolve(null);
                return;
            }

            setTimeout(tick, intervalMs);
        };

        tick();
    });
}

/**
 * Re-apply Shepherd modal cutout and target classes after DOM updates.
 */
function refreshShepherdStepTarget(step) {
    if (!step?.options?.attachTo || !step.tour?.modal) {
        return false;
    }

    const attachOn = step.options.attachTo.on || 'right';
    const selector = `[data-tour="${step.id}"]`;
    const target = resolveTourTargetElement(step.id, selector);

    if (!target) {
        return false;
    }

    if (step.target && step.target !== target) {
        step._updateStepTargetOnHide();
    }

    step._resolvedAttachTo = null;
    step.options.attachTo = {
        element: target,
        on: attachOn,
    };
    step._resolveAttachToOptions();
    step.target = target;

    if (step.tour.options.useModalOverlay) {
        step.tour.modal.setupForStep(step);
        if (typeof step.tour.modal.show === 'function') {
            step.tour.modal.show();
        }
    }

    step._styleTargetElementForStep(step);

    return true;
}

function scheduleStepTargetRefresh(step) {
    if (!step?.options?.attachTo) {
        return;
    }

    const run = () => refreshShepherdStepTarget(step);

    run();
    requestAnimationFrame(() => {
        run();
        requestAnimationFrame(run);
    });

    [100, 350, 700, 1200].forEach((delay) => setTimeout(run, delay));
}

function highlightNavForStep(stepId) {
    document.querySelectorAll('.shepherd-tour-active-nav').forEach((item) => {
        item.classList.remove('shepherd-tour-active-nav');
    });

    const navItem = document.querySelector(`[data-tour="${stepId}"]`);

    if (!navItem) {
        return;
    }

    const navContainer =
        navItem.closest('.fi-sidebar-item') ||
        navItem.closest('.fi-sidebar-group-item') ||
        navItem.closest('.fi-sidebar-item-button') ||
        navItem.closest('li') ||
        navItem;

    navContainer.classList.add('shepherd-tour-active-nav');

    if (navItem !== navContainer) {
        navItem.classList.add('shepherd-tour-active-nav');
    }

    navContainer.scrollIntoView({
        behavior: 'smooth',
        block: 'nearest',
        inline: 'nearest',
    });
}

function waitAfterNavigation() {
    return new Promise((resolve) => {
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                applyTourNavAttributes();
                setTimeout(resolve, 200);
            });
        });
    });
}

/**
 * Full cleanup when the tour ends (complete / cancel).
 */
function cleanupTourState() {
    document.querySelectorAll('.shepherd-tour-active-nav').forEach((item) => {
        item.classList.remove('shepherd-tour-active-nav');
    });

    document.querySelectorAll('.tour-original-active').forEach((item) => {
        item.classList.remove('tour-original-active');
    });

    document.querySelectorAll('.shepherd-target, .shepherd-enabled').forEach((el) => {
        el.classList.remove(
            'shepherd-target',
            'shepherd-enabled',
            'shepherd-target-click-disabled',
        );
    });

    document.querySelectorAll('.shepherd-modal-overlay-container').forEach((el) => {
        el.remove();
    });

    activeTourInstance = null;
    syncSidebarActiveState();
}

/**
 * Mark only the sidebar item matching the current URL as active.
 */
function syncSidebarActiveState() {
    const currentPath = window.location.pathname;

    document.querySelectorAll('.fi-sidebar-item.fi-active, .fi-sidebar-group-item.fi-active').forEach((item) => {
        item.classList.remove('fi-active');
    });

    document.querySelectorAll('a.fi-sidebar-item-btn[href]').forEach((link) => {
        let linkPath;

        try {
            linkPath = new URL(link.href, window.location.origin).pathname;
        } catch (e) {
            return;
        }

        if (linkPath !== currentPath) {
            return;
        }

        const item = link.closest('.fi-sidebar-item') || link.closest('.fi-sidebar-group-item');

        if (item) {
            item.classList.add('fi-active');
        }
    });
}

function hideModalOverlay(step) {
    if (step?.tour?.modal?.hide) {
        step.tour.modal.hide();
    }
}

function navigateToStepUrl(stepData) {
    return new Promise((resolve) => {
        if (!stepData.url) {
            resolve();
            return;
        }

        const currentUrl = window.location.pathname;
        const targetUrl = new URL(stepData.url, window.location.origin).pathname;

        if (currentUrl === targetUrl) {
            waitAfterNavigation().then(resolve);
            return;
        }

        const finish = () => waitAfterNavigation().then(resolve);

        if (typeof Livewire !== 'undefined' && Livewire.navigate) {
            document.addEventListener('livewire:navigated', function handler() {
                document.removeEventListener('livewire:navigated', handler);
                finish();
            }, { once: true });

            Livewire.navigate(stepData.url);
            return;
        }

        window.location.href = stepData.url;
    });
}

export function initializeShepherdTour() {
    const tour = new Shepherd.Tour({
        useModalOverlay: true,
        defaultStepOptions: {
            classes: 'shepherd-theme-custom',
            scrollTo: { behavior: 'smooth', block: 'center' },
            cancelIcon: {
                enabled: true,
                label: window.tourTranslations?.buttons?.cancel || 'Close',
            },
            modalOverlayOpeningRadius: 12,
            modalOverlayOpeningPadding: 10,
        },
        tourName: 'app-tour',
    });

    activeTourInstance = tour;

    tour.on('start', () => {
        applyTourNavAttributes();
    });

    tour.on('show', (event) => {
        if (!event.step) {
            return;
        }

        localStorage.setItem('shepherd-tour-current-step', event.step.id);
        localStorage.setItem('shepherd-tour-in-progress', 'true');
    });

    tour.on('complete', () => {
        localStorage.removeItem('shepherd-tour-current-step');
        localStorage.removeItem('shepherd-tour-in-progress');
        localStorage.setItem('shepherd-tour-completed', 'true');
        localStorage.setItem('shepherd-tour-completed-at', new Date().toISOString());
        cleanupTourState();
    });

    tour.on('cancel', () => {
        localStorage.removeItem('shepherd-tour-current-step');
        localStorage.removeItem('shepherd-tour-in-progress');
        cleanupTourState();
    });

    const dynamicSteps = window.dynamicTourSteps || [];
    const welcomeStep = window.customWelcomeStep;
    const finishStep = window.customFinishStep;

    const allSteps = [];
    if (welcomeStep) {
        allSteps.push(welcomeStep);
    }
    allSteps.push(...dynamicSteps);
    if (finishStep) {
        allSteps.push(finishStep);
    }

    allSteps.forEach((stepData) => {
        const stepConfig = {
            id: stepData.id,
            title: stepData.title,
            text: stepData.text,
        };

        const attachToSelector = stepData.attachTo || null;

        if (stepData.url || attachToSelector) {
            stepConfig.beforeShowPromise = function () {
                return navigateToStepUrl(stepData).then(() => {
                    if (!attachToSelector) {
                        return null;
                    }

                    return waitForTourTarget(stepData.id, attachToSelector);
                });
            };
        }

        if (attachToSelector) {
            stepConfig.attachTo = {
                element() {
                    return resolveTourTargetElement(this.id, attachToSelector);
                },
                on: stepData.position || 'right',
            };

            stepConfig.when = {
                show() {
                    applyTourNavAttributes();
                    highlightNavForStep(this.id);
                    scheduleStepTargetRefresh(this);
                },
            };
        } else {
            // Welcome / finish: centered tooltip without full-screen dark overlay
            const existingWhen = stepConfig.when || {};

            stepConfig.when = {
                ...existingWhen,
                show() {
                    if (typeof existingWhen.show === 'function') {
                        existingWhen.show.call(this);
                    }

                    hideModalOverlay(this);
                },
            };
        }

        const buttons = [];
        const stepButtons = stepData.buttons || [
            { text: window.tourTranslations?.buttons?.previous || 'Previous', action: 'back', secondary: true },
            { text: window.tourTranslations?.buttons?.next || 'Next', action: 'next', secondary: false },
        ];

        stepButtons.forEach((btnData) => {
            const button = {
                text: btnData.text,
                secondary: btnData.secondary || false,
            };

            if (btnData.action === 'back') {
                button.action = tour.back;
            } else if (btnData.action === 'next') {
                button.action = tour.next;
            } else if (btnData.action === 'cancel') {
                button.action = tour.cancel;
            } else if (btnData.action === 'complete') {
                button.action = tour.complete;
            }

            buttons.push(button);
        });

        stepConfig.buttons = buttons;
        tour.addStep(stepConfig);
    });

    return tour;
}

document.addEventListener('DOMContentLoaded', function () {
    applyTourNavAttributes();
    setTimeout(applyTourNavAttributes, 1000);

    document.addEventListener('livewire:navigated', () => {
        setTimeout(() => {
            applyTourNavAttributes();

            const currentStep = activeTourInstance?.getCurrentStep?.();

            if (currentStep) {
                highlightNavForStep(currentStep.id);
                scheduleStepTargetRefresh(currentStep);
            }
        }, 100);
    });

    const observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
            if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                clearTimeout(window.tourAttributeTimeout);
                window.tourAttributeTimeout = setTimeout(applyTourNavAttributes, 150);
            }
        });
    });

    const sidebar = document.querySelector('.fi-sidebar');

    if (sidebar) {
        observer.observe(sidebar, {
            childList: true,
            subtree: true,
        });
    }

    document.querySelectorAll('[data-shepherd-tour-trigger]').forEach((button) => {
        button.addEventListener('click', function (e) {
            e.preventDefault();

            try {
                const tour = initializeShepherdTour();
                const inProgress = localStorage.getItem('shepherd-tour-in-progress');
                const currentStepId = localStorage.getItem('shepherd-tour-current-step');

                if (inProgress === 'true' && currentStepId) {
                    tour.show(currentStepId);
                } else {
                    tour.start();
                }
            } catch (error) {
                console.error('Error starting tour:', error);
                alert('An error occurred while starting the tour. Please try again.');
            }
        });
    });

    const inProgress = localStorage.getItem('shepherd-tour-in-progress');
    const currentStepId = localStorage.getItem('shepherd-tour-current-step');

    if (inProgress === 'true' && currentStepId) {
        setTimeout(() => {
            try {
                const tour = initializeShepherdTour();
                tour.show(currentStepId);
            } catch (error) {
                console.error('Error auto-resuming tour:', error);
                localStorage.removeItem('shepherd-tour-in-progress');
                localStorage.removeItem('shepherd-tour-current-step');
            }
        }, 1500);
    }
});

export default initializeShepherdTour;
