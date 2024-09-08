import { StrictMode } from "react";
import { createRoot } from "react-dom/client";
import App from "./App.jsx";
import "./index.css";

createRoot(document.getElementById("simply-backitup-settings")).render(
	<StrictMode>
		<App data={SimplyBackItUp} />
	</StrictMode>
);