const DiscouragedHeader = ({listURL}) =>{
	return(
		<div className="gv-mt-md">
			<div className="oc-header-wrap">
				<p className="gv-text-lg gv-text-bold">{ocpluginVars?.headingDiscouragedPlugins || "Discouraged plugins"}</p>
				<div className="gv-mode-condensed">
					<a className="gv-button gv-button-secondary gv-max-mob-hidden ocwp_ocp_plugins_discourage_plugins_list_viewed_event" href={listURL} target="_blank">
						<span>{ocpluginVars?.viewDiscouragedPlugins || "View Discouraged Plugins"}</span>
						<gv-icon
							src="/wp-content/plugins/onecom-themes-plugins/assets/images/open_in_new.svg"></gv-icon>
					</a>
				</div>
			</div>
			<p className="gv-mt-sm gv-mb-md gv-text-sm">{ocpluginVars?.discouragedPluginDesc || "Keep your WordPress site running smoothly. We review your plugins and list those we don’t recommend using."}</p>
			<div className="gv-mode-condensed">
				<a className="gv-button gv-button-secondary gv-desk-hidden gv-tab-hidden gv-mb-md ocwp_ocp_plugins_discourage_plugins_list_viewed_event" href={listURL} target="_blank">
					<span>{ocpluginVars?.viewDiscouragedPlugins || "View Discouraged Plugins"}</span>
					<gv-icon src="/wp-content/plugins/onecom-themes-plugins/assets/images/open_in_new.svg"></gv-icon>
				</a>
			</div>
		</div>
	)
}

export default DiscouragedHeader;