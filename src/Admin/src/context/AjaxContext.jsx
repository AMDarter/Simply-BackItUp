import React, { createContext, useContext } from 'react';

const AjaxContext = createContext();

export const useAjax = () => useContext(AjaxContext);

export const AjaxProvider = ({ children, ajaxUrl, nonce }) => {
    return (
        <AjaxContext.Provider value={{ ajaxUrl, nonce }}>
            {children}
        </AjaxContext.Provider>
    );
};
