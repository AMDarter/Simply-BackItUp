export const recursiveAppendFormData = (formData, key, data) => {
	if (Array.isArray(data)) {
		for (const [index, subData] of data.entries()) {
			recursiveAppendFormData(formData, `${key}[${index}]`, subData);
		}
	} else if (typeof data === "object" && data !== null) {
		for (const [subKey, subData] of Object.entries(data)) {
			recursiveAppendFormData(formData, `${key}[${subKey}]`, subData);
		}
	} else {
		formData.append(key, data);
	}
};

export const render24HourTimeOptions = () => {
	return [...Array(24)].map((_, i) => (
		<option
			key={i}
			value={`${i.toString().padStart(2, "0")}:00`}
		>
			{i.toString().padStart(2, "0")}:00
		</option>
	));
};

export const performBackupStep = async ({
    ajaxUrl,
    action,
    nonce,
}) => {
    const formData = new FormData();
    formData.append("action", action);
    formData.append("nonce", nonce);

    const { success, data, error } = await submitFormData(ajaxUrl, formData);

    if (success) {
        return {
            success: true,
            progress: {
                value: data?.data?.progress,
                message: data?.data?.message,
            },
        };
    } else {
        return { success: false, message: data?.data?.message || error };
    }
};

export 	const submitFormData = async (ajaxUrl, formData) => {
    try {
        const response = await fetch(ajaxUrl, {
            method: "POST",
            body: formData,
        });
        const responseData = await response.json();
        return { success: responseData?.success, data: responseData };
    } catch (error) {
        return { success: false, error: error.message };
    }
};