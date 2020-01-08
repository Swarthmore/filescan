import React from "react";
import ReactDOM from "react-dom";
import DataTable from "./components/DataTable";

const App = () => {
  return (
    <div className="app">
      <DataTable />
    </div>
  );
};
ReactDOM.render(<App />, document.getElementById("summary-root"));
