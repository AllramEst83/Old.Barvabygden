import DiscouragedHeader from "./DiscouragedHeader";

const NoDiscouragedPlugins = ({listUrl}) => {
    return (
		<>
			<DiscouragedHeader listURL={listUrl} />
            <div className="gv-content-container gv-surface-bright gv-p-fluid gv-text-center">
                <h5 className='gv-mb-sm'>{ocpluginVars?.wellDone || "Well done!"}</h5>
                <p>{ocpluginVars?.noDiscouragedPlugins || "No discouraged plugins found on your site."}</p>
            </div>
		</>
    );
}
export default NoDiscouragedPlugins;