<style>
/* Critical app-shell layout (sidebar + topbar) — uses CSS variables for dark mode */
html.ic-app, body.ic-app { height: 100%; margin: 0; background: var(--ic-bg); }
.ic-app *, .ic-app *::before, .ic-app *::after { box-sizing: border-box; }
.ds-shell { display: flex; min-height: 100vh; }
.ds-sidebar {
    width: 220px; flex-shrink: 0; background: var(--ic-surface);
    border-right: 1px solid var(--ic-border); display: flex; flex-direction: column;
    position: fixed; top: 0; left: 0; height: 100vh; z-index: 200; overflow-y: auto;
    transition: transform 0.25s ease;
}
.ds-main {
    margin-left: 220px; flex: 1; display: flex; flex-direction: column;
    min-height: 100vh; width: calc(100% - 220px);
}
.ds-sidebar-overlay {
    display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.4); z-index: 150;
}
.sb-brand {
    padding: 1.25rem 1.25rem 0.75rem; display: flex; align-items: center;
    gap: 0.55rem; text-decoration: none;
}
.sb-brand .sb-icon {
    width: 34px; height: 34px; border-radius: 8px; background: var(--ic-primary);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 1rem; flex-shrink: 0;
}
.sb-brand .sb-name { font-weight: 700; font-size: 0.95rem; color: var(--ic-text); line-height: 1.1; }
.sb-brand .sb-sub { font-size: 0.68rem; color: var(--ic-text-light); }
.sb-nav { flex: 1; padding: 0.5rem 0; }
.sb-nav a {
    display: flex; align-items: center; gap: 0.65rem; padding: 0.6rem 1.25rem;
    font-size: 0.875rem; font-weight: 500; color: var(--ic-text-muted); text-decoration: none;
    transition: background 0.15s, color 0.15s; position: relative;
}
.sb-nav a:hover { background: var(--ic-bg); color: var(--ic-text); }
.sb-nav a.active { background: var(--ic-primary-light); color: var(--ic-primary); font-weight: 600; }
.sb-nav a.active::before {
    content: ''; position: absolute; left: 0; top: 0; bottom: 0;
    width: 3px; background: var(--ic-primary); border-radius: 0 2px 2px 0;
}
.sb-nav i { font-size: 1rem; }
.ds-topbar {
    background: var(--ic-surface); border-bottom: 1px solid var(--ic-border); padding: 0.75rem 1.5rem;
    display: flex; align-items: center; gap: 1rem; position: sticky; top: 0; z-index: 90;
}
.ds-menu-btn {
    display: none; background: none; border: 1px solid var(--ic-border); border-radius: 0.5rem;
    padding: 0.35rem 0.55rem; color: var(--ic-text-muted); font-size: 1.2rem; cursor: pointer; line-height: 1;
}
.ds-topbar .topbar-title { font-size: 0.95rem; font-weight: 700; color: var(--ic-text); }
.ds-topbar .topbar-right { margin-left: auto; display: flex; align-items: center; gap: 1rem; }
.ds-topbar .search-box { flex: 1; max-width: 400px; position: relative; }
.ds-topbar .search-box input {
    width: 100%; border: 1.5px solid var(--ic-border); border-radius: 2rem;
    padding: 0.45rem 1rem 0.45rem 2.5rem; font-size: 0.85rem; color: var(--ic-text);
    background: var(--ic-bg); outline: none;
}
.ds-topbar .search-box i {
    position: absolute; left: 0.85rem; top: 50%; transform: translateY(-50%);
    color: var(--ic-text-light); font-size: 0.9rem;
}
.user-chip { display: flex; align-items: center; gap: 0.6rem; text-decoration: none; color: var(--ic-text); }
.user-chip .avatar {
    width: 36px; height: 36px; border-radius: 50%; background: var(--ic-primary);
    color: #fff; font-weight: 700; font-size: 0.9rem;
    display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0;
}
.user-chip .avatar img { width: 100%; height: 100%; object-fit: cover; }
.user-chip .u-name { font-size: 0.85rem; font-weight: 600; line-height: 1.2; }
.user-chip .u-sub { font-size: 0.72rem; color: var(--ic-text-light); }
.logout-btn {
    background: none; border: none; padding: 0.35rem; color: var(--ic-text-light);
    font-size: 1.1rem; cursor: pointer; text-decoration: none; display: flex; align-items: center;
}
.ds-body { padding: 1.75rem 2rem; flex: 1; }
.ds-footer {
    border-top: 1px solid var(--ic-border); padding: 0.9rem 2rem; background: var(--ic-surface);
    display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem;
}
@media (max-width: 767px) {
    .ds-sidebar { transform: translateX(-100%); }
    .ds-sidebar.open { transform: translateX(0); }
    .ds-sidebar-overlay.open { display: block; }
    .ds-main { margin-left: 0; width: 100%; }
    .ds-menu-btn { display: inline-flex; align-items: center; justify-content: center; }
}
</style>
