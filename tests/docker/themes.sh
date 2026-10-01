# Sourced by the check scripts that run under more than one theme (template-check.sh, isolation-check.sh).
# Expects COMPOSE and wp() to be defined, and the working directory to be the repository root.
#
#   twentytwentyfive  a block theme (installed with WordPress)
#   breakdance-zero   Breakdance's own theme, shipped inside the Breakdance plugin
#   evpx-classic      a bare classic theme: tests/docker/classic-theme

THEMES_DIR=/var/www/html/wp-content/themes

# Remember the active theme so it can be put back. If an earlier, interrupted run left one of the
# fixture themes active, fall back to a stock theme.
remember_theme() {
	ORIGINAL_THEME="$(wp theme list --status=active --field=name 2>/dev/null | tail -1)"
	case "$ORIGINAL_THEME" in evpx-classic | breakdance-zero | "") ORIGINAL_THEME=twentytwentyfive ;; esac
}

install_test_themes() {
	"${COMPOSE[@]}" exec -T wordpress rm -rf "$THEMES_DIR/evpx-classic" "$THEMES_DIR/breakdance-zero"
	"${COMPOSE[@]}" cp tests/docker/classic-theme wordpress:"$THEMES_DIR/evpx-classic"
	"${COMPOSE[@]}" exec -T wordpress cp -r /var/www/html/wp-content/plugins/breakdance/plugin/themeless/themes/breakdance-zero "$THEMES_DIR/breakdance-zero"
	"${COMPOSE[@]}" exec -T wordpress chown -R www-data:www-data "$THEMES_DIR/evpx-classic" "$THEMES_DIR/breakdance-zero"
}

# Puts the original theme back and removes the two fixtures.
restore_themes() {
	wp theme activate "$ORIGINAL_THEME" >/dev/null 2>&1
	"${COMPOSE[@]}" exec -T wordpress rm -rf "$THEMES_DIR/evpx-classic" "$THEMES_DIR/breakdance-zero" >/dev/null 2>&1
}
