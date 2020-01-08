import React, { useState, useEffect } from "react";
import ReactDOM from "react-dom";
import ProgressBar from "react-bootstrap/ProgressBar";
import Spinner from "react-bootstrap/Spinner";
import Row from "react-bootstrap/Row";
import Col from "react-bootstrap/Col";
import Container from "react-bootstrap/Container";
import Ajax from "./lib/ajax";

const App = () => {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [totalRecords, setTotalRecords] = useState(0);
  const [recordsWithText, setRecordsWithText] = useState(0);
  const [recordsWithOutline, setRecordsWithOutline] = useState(0);
  const [recordsWithLanguage, setRecordsWithLanguage] = useState(0);
  const [recordsWithTitle, setRecordsWithTitle] = useState(0);

  const makeArgs = (total, offset) => {
    return {
      draw: 1, // this number doesn't really matter
      columns: [
        {
          data: "status",
          name: "",
          searchable: true,
          orderable: true,
          search: {
            value: "",
            regex: false
          }
        },
        {
          data: "hastext",
          name: "",
          searchable: true,
          orderable: true,
          search: {
            value: "",
            regex: false
          }
        },
        {
          data: "hastitle",
          name: "",
          searchable: true,
          orderable: true,
          search: {
            value: "",
            regex: false
          }
        },
        {
          data: "hasoutline",
          name: "",
          searchable: true,
          orderable: true,
          search: {
            value: "",
            regex: false
          }
        },
        {
          data: "haslanguage",
          name: "",
          searchable: true,
          orderable: true,
          search: {
            value: "",
            regex: false
          }
        },
        {
          data: "timechecked",
          name: "",
          searchable: true,
          orderable: true,
          search: {
            value: "",
            regex: false
          }
        },
        {
          data: "courseinfo",
          name: "",
          searchable: true,
          orderable: true,
          search: {
            value: "",
            regex: false
          }
        }
      ],
      order: [
        {
          column: 0,
          dir: "asc"
        }
      ],
      start: offset,
      length: total,
      search: {
        value: "",
        regex: false
      }
    };
  };

  useEffect(async () => {
    setLoading(true);
    const total = await getTotalRecords();
    setTotalRecords(total);
    const records = await getAllRecords(total);

    const hasText = records.filter(row => row.hastext).length || 0;
    const hasOutline = records.filter(row => row.hasoutline).length || 0;
    const hasLanguage = records.filter(row => row.haslanguage).length || 0;
    const hasTitle = records.filter(row => row.hastitle).length || 0;

    setRecordsWithLanguage(hasLanguage);
    setRecordsWithOutline(hasOutline);
    setRecordsWithText(hasText);
    setRecordsWithTitle(hasTitle);

    setLoading(false);
  }, []); // this only needs to run once!

  const getTotalRecords = () => {
    return new Promise((resolve, reject) => {
      Ajax.call([
        {
          methodname: "block_afs_request_files",
          args: makeArgs(1, 0),
          done: res => {
            const { recordsTotal } = res;
            resolve(recordsTotal);
          },
          fail: () => reject("Could not get total records!")
        }
      ]);
    });
  };

  const getAllRecords = count => {
    return new Promise((resolve, reject) => {
      Ajax.call([
        {
          methodname: "block_afs_request_files",
          args: makeArgs(count, 0),
          done: res => {
            const records = res.data;
            resolve(records);
          },
          fail: () => reject("Could not get total records!")
        }
      ]);
    });
  };

  const progressBarStyles = {
    width: "100%",
    minHeight: "40px"
  };

  return (
    <Container fluid>
      {loading && (
        <div>
          <Spinner animation="border" role="status"></Spinner>
          <span className={"text-muted"}>Generating your report...</span>
        </div>
      )}
      <div className={"mb-2"}>
        <p className={"text-lead"}>PDFs with text</p>
        <ProgressBar
          style={progressBarStyles}
          animated
          now={recordsWithText}
          max={totalRecords}
          label={` ${recordsWithText} / ${totalRecords}`}
        />
      </div>
      <div className={"mb-2"}>
        <p className={"text-lead"}>PDFs with an outline</p>
        <ProgressBar
          style={progressBarStyles}
          animated
          now={recordsWithOutline}
          max={totalRecords}
          label={`${recordsWithOutline} / ${totalRecords}`}
        />
      </div>
      <div className={"mb-2"}>
        <p className={"text-lead"}>PDFs with a language</p>
        <ProgressBar
          style={progressBarStyles}
          animated
          now={recordsWithLanguage}
          max={totalRecords}
          label={`${recordsWithLanguage} / ${totalRecords}`}
        />
      </div>
      <div className={"mb-2"}>
        <p className={"text-lead"}>PDFs with a title</p>
        <ProgressBar
          style={progressBarStyles}
          animated
          now={recordsWithTitle}
          max={totalRecords}
          label={`${recordsWithTitle} / ${totalRecords}`}
        />
      </div>
    </Container>
  );
};

ReactDOM.render(<App />, document.getElementById("afs-progress-bars"));
