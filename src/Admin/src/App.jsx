import { useEffect, useState } from "react";
import "./App.css";
import SettingsForm  from "./Components/SettingsForm";

function App({ data }) {
	return (
		<div>
			<h1>Simply BackItUp</h1>
			<SettingsForm settings={data.settings} ajaxUrl={data.ajaxurl} nonce={data.nonce} />
		</div>
	);
}

export default App;
