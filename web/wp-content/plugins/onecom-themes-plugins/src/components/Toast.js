import { useState, useEffect } from '@wordpress/element';
import { createPortal } from "@wordpress/element";
import {usePluginContext} from "../PluginContext";

const Toast = () => {
	const { toastData, setToastData } = usePluginContext();
	const { message, type } = toastData;
	const [visible, setVisible] = useState(false);

	useEffect(() => {
		if (!message) return; // Don't run effect if there's no message

		setVisible(true);
		const timeout = setTimeout(() => {
			setVisible(false);
			setToastData({ type: "", message: "" }); // Clear toast data
		}, 5000);

		return () => clearTimeout(timeout); // Cleanup on unmount
	}, [message, setToastData]);

	if (!message) return null; // Prevent rendering when there's no message

	const handleClose = () => {
		setVisible(false);
		setToastData({ type: "", message: "" });
	};

	const toastContent = (
		<div className={`gv-toast gv-toast-${type} ${visible ? "gv-visible" : "gv-invisible"}`}>
			<p className="gv-toast-content">{message}</p>
			<button className="gv-toast-close" onClick={handleClose}>
				<img src="/wp-content/plugins/onecom-themes-plugins/assets/images/close.svg" alt="Close" />
			</button>
		</div>
	);

	const toastContainer = document.getElementById("oc-toast-content");
	return toastContainer ? createPortal(toastContent, toastContainer) : toastContent;
};

export default Toast;