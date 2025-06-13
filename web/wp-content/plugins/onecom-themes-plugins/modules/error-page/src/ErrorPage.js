import {StrictMode, createRoot, useState} from '@wordpress/element';
import {Radio, Button, Toast, Notice, eventEmitter} from "wpin-components-library";

const getInitialState = (value) => {
    return ["true", "1", 1].includes(value) ? "1" : "0";
};
const noticePlaceholder = document.getElementById("oc-notice-placeholder");

if (noticePlaceholder) {
    const root = createRoot(noticePlaceholder);
    root.render(<Notice closable={false} />);
}
const ErrorPage = () => {

    const [pageState, setpageState] = useState(getInitialState(window.ErrorPage?.status));
    const [isLoading, setisLoading] = useState(false);
    const [toastData, settoastData] = useState({message: "", type: "info"});
    const handleOnchange = (value) => {
        setpageState(value);
    }

    const handleSave = async () => {
        setisLoading(true);
        const data = {
            action: 'onecom-error-pages',
            type: pageState === '1' ? 'enable' : 'disable'
        };

        try {
            const response = await fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                },
                body: new URLSearchParams(data).toString(),
            });

            const result = await response.json();

            // Handle success or error from the server response
            console.log('Server response:', result);
            if(result.success){
			eventEmitter.emit("hideNotice");

				if (result.data.status === 'success') {
					settoastData({
						message: result.data.message || "Your settings were saved.",
						type: "success"
					});
				} else {
					settoastData({
						message: result.data.message || "Couldn’t save your settings.",
						type: "alert"
					});
				}
			}else {
				setpageState("0");
                eventEmitter.emit('showNotice', {
                    title: window.ErrorPage.noticeHeading,
                    message: window.ErrorPage.noticeDescription,
                    icon: `${window.ErrorPage.imageURL}/modules/error-page/assets/img/error.svg`,
                    noticeClass: 'gv-notice-alert',
                });

            }
        } catch (error) {
            console.error('Error saving data:', error);
            settoastData({message: "Couldn’t save your settings.", type: "alert"});
        } finally {
            setisLoading(false);
        }
    };

    return (
        <>
            <Toast
                message={toastData.message}
                type={toastData.type}
                onClose={() => settoastData({message: "", type: ""})}
                containerId="oc-error-toast"
                closeIcon={`${window.ErrorPage.imageURL}/assets/images/close.svg`}
            />
            <div className="gv-flex gv-justify-between gv-max-mob-flex-col">
                <div className='oc-left'>
                    <p className="gv-text-sm gv-text-bold gv-mb-sm">{window.ErrorPage.labelStatus}</p>
                    <div className="gv-mode-condensed gv-mb-md gv-form-option">
                        <Radio id='oc-active' name="status" value="1" checked={pageState === "1"}
                               onChange={(e) => handleOnchange(e.target.value)} label={window.ErrorPage.labelActive}/>
                    </div>
                    <div className="gv-mode-condensed gv-form-option">
                        <Radio id='oc-inactive' name="status" value="0" checked={pageState === "0"}
                               onChange={(e) => handleOnchange(e.target.value)} label={window.ErrorPage.labelInactive}/>
                    </div>
                    <Button className='gv-button-primary gv-mt-md gv-max-mob-hidden' onClick={(e) => handleSave(e)}
                            type='button'
                            children={isLoading ? window.ErrorPage.labelSaving : window.ErrorPage.labelSave}/>
                </div>
                <div className="oc-right gv-flex-auto gv-max-mob-mt-md">
                    {pageState === "1" ? (
                        <img
                            src={`${window.ErrorPage.imageURL}/modules/error-page/assets/img/error-active.svg`}/>
                    ) : (
                        <img
                            src={`${window.ErrorPage.imageURL}/modules/error-page/assets/img/error-inactive.svg`}/>
                    )
                    }

                </div>
                <Button className='gv-button-primary gv-mt-md gv-tab-hidden gv-desk-hidden'
                        onClick={(e) => handleSave(e)} type='button'
                        children={isLoading ? window.ErrorPage.labelSaving : window.ErrorPage.labelSave}/>

            </div>
        </>
    );
};

const rootElement = document.getElementById("oc-errorpage-root");
if (rootElement) {
    const root = createRoot(rootElement);
    root.render(
        <StrictMode>
            <ErrorPage/>
        </StrictMode>
    );
}