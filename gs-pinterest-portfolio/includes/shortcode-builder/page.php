<div class="app-container">
	<div class="main-container">
		<div id="gspin-shortcode-app">
			<header class="gspin-header">
				<div class="gs-containeer-f">
					<div class="gs-roow">
						<div class="logo-area gs-col-xs-6">
							<router-link to="/">
								<img src="<?php echo GSPIN_PLUGIN_URI . '/assets/img/icon.svg'; ?>" alt="GS Pinterest Logo">
							</router-link>
						</div>
						<div class="menu-area gs-col-xs-6 text-right">
							<ul>
								<router-link to="/" tag="li"><a><?php _e( 'Shortcodes', 'gs-pinterest' ); ?></a></router-link>
								<router-link to="/shortcode" tag="li"><a><?php _e( 'Create New', 'gs-pinterest' ); ?></a></router-link>
								<router-link to="/preferences" tag="li"><a><?php _e( 'Preferences', 'gs-pinterest' ); ?></a></router-link>
								<router-link to="/tools" tag="li"><a><?php _e( 'Tools', 'gs-pinterest' ); ?></a></router-link>
							</ul>
						</div>
					</div>
				</div>
			</header>

			<div class="gspin-app-view-container">
				<router-view :key="$route.fullPath"></router-view>
			</div>

		</div>		
	</div>
</div>