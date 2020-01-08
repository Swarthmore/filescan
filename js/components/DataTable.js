import React, { useState, useEffect } from "react";
import Table from "react-bootstrap/Table";
import Container from "react-bootstrap/Container";
import Badge from "react-bootstrap/Badge";
import Col from "react-bootstrap/Col";
import Form from "react-bootstrap/Form";
import Row from "react-bootstrap/Row";
import Spinner from "react-bootstrap/Spinner";
import Ajax from "../lib/ajax";
import ReactPaginate from "react-paginate";
import Button from "react-bootstrap/Button";
import { CSVLink, CSVDownload } from "react-csv";

const DataTable = () => {
  const [currentPage, setCurrentPage] = useState(1);
  const [filter, setFilter] = useState("");
  const [limitPerPage, setLimitPerPage] = useState(10);

  const [loadingAll, setLoadingAll] = useState(false);
  const [allRecords, setAllRecords] = useState(null);

  const config = M.cfg; // Moodle config global
  const pageRange = 5;

  const useFetch = args => {
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState("");
    const [currentRows, setCurrentRows] = useState([]);
    const [totalRecords, setTotalRecords] = useState(0);
    const [totalPages, setTotalPages] = useState(0);

    useEffect(() => {
      setLoading(true);
      Ajax.call([
        {
          methodname: "block_afs_request_files",
          args,
          done: res => {
            const { recordsTotal, data } = res;
            const pages = Math.ceil(recordsTotal / limitPerPage);
            setTotalRecords(recordsTotal);
            setCurrentRows(data);
            setTotalPages(pages);
            setLoading(false);
          },
          fail: () => {
            setLoading(false);
            setError("Failed to retrieve records from Moodle web service");
          }
        }
      ]);
    }, [currentPage, filter, limitPerPage]);

    return { currentRows, loading, totalRecords, totalPages, error };
  };

  // Return arguments required to make Moodle AJAX request
  const makeArgs = data => {
    const { currentPage, limitPerPage, filter } = data;

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
      start: currentPage * limitPerPage,
      length: limitPerPage,
      search: {
        value: filter,
        regex: false
      }
    };
  };

  // Get field from courseinfo object
  const getCourseinfoField = (field, courseinfo) => {
    const data = JSON.parse(courseinfo)[0];
    if (data) {
      return data[field];
    } else {
      data[field] = "";
      return data[field];
    }
  };

  const handleSelectChange = e => {
    const { target } = e;
    const { value } = target;
    setLimitPerPage(value);
  };

  const handleFilterChange = e => {
    const { target } = e;
    const { value } = target;
    setFilter(value);
  };

  const getAllRecords = count => {
    return new Promise((resolve, reject) => {
      Ajax.call([
        {
          methodname: "block_afs_request_files",
          args: makeArgs({ currentPage: 0, limitPerPage: count, filter: "" }),
          done: res => {
            const records = res.data;
            resolve(records);
          },
          fail: () => reject("Could not get all records!")
        }
      ]);
    });
  };

  const handleExportBtnClick = async () => {
    try {
      setLoadingAll(true);
      const data = await getAllRecords(totalRecords);
      setAllRecords(data);
    } catch (error) {
      console.error(error);
    }
    setLoadingAll(false);
  };

  // Styles and classes
  const faSuccessClass = "fa fa-check fa-fw text-success";
  const faFailClass = "fa fa-exclamation-triangle fa-jw text-warning";

  const tableCellStyles = {
    verticalAlign: "middle"
  };

  const tableHeaderStyles = {
    fontWeight: "normal"
  };

  const args = makeArgs({
    currentPage,
    limitPerPage,
    filter
  });

  const { currentRows, loading, totalRecords, totalPages, error } = useFetch(
    args
  );

  return (
    <Container fluid>
      <Row className={"mb-3"}>
        <Col>
          {error && <p className={"text-error"}>{error}</p>}
          {loading && (
            <p>
              Fetching data...
              <Spinner animation={"border"} />
            </p>
          )}
        </Col>
        <Col>
          <Button
            className={"float-right"}
            variant={"primary"}
            onClick={handleExportBtnClick}
            disabled={loadingAll}
          >
            {loadingAll && <Spinner animation="border" />}
            {loadingAll ? "Preparing.." : "Export as CSV"}
          </Button>

          {allRecords && (
            <CSVLink
              headers={[
                { label: "Id", key: "id" },
                { label: "Status", key: "status" },
                { label: "Has Text", key: "hastext" },
                { label: "Has Outline", key: "hasoutline" },
                { label: "Has Language", key: "haslanguage" },
                { label: "Time Checked", key: "timechecked" },
                { label: "Course Info", key: "courseinfo" }
              ]}
              data={allRecords}
            >
              Your download is ready
            </CSVLink>
          )}
        </Col>
      </Row>

      <Row>
        <Col>
          <ReactPaginate
            previousLabel={"<<"}
            nextLabel={">>"}
            breakLabel={"..."}
            onPageChange={({ selected }) => setCurrentPage(selected)}
            containerClassName={"pagination"}
            pageClassName={"page-item"}
            pageLinkClassName={"page-link"}
            activeClassName={"active"}
            pageCount={totalPages}
            pageRangeDisplayed={pageRange}
            marginPagesDisplayed={2}
            disabledClassName={"disabled"}
            nextLinkClassName={"page-link"}
            previousLinkClassName={"page-link"}
            breakClassName={"page-link"}
            hrefBuilder={() => "#"}
          />
        </Col>

        <Col>
          <Form.Group as={Row}>
            <Form.Label column sm={4}>
              Show per page
            </Form.Label>
            <Col sm={8}>
              <Form.Control as="select" size="md" onChange={handleSelectChange}>
                {[10, 20, 30, 40, 50].map(num => (
                  <option key={"num"} value={num}>
                    {num}
                  </option>
                ))}
              </Form.Control>
            </Col>
          </Form.Group>
        </Col>
      </Row>

      <Row>
        {/* The filter input */}
        <Col>
          <Form.Control
            size="lg"
            className={"mb-3"}
            type="text"
            placeholder="Filter your results"
            onChange={handleFilterChange}
          />
        </Col>
      </Row>

      {/* The table */}
      <Row>
        <Col>
          <Table bordered>
            <thead>
              <tr>
                <th>Status</th>
                <th>Text</th>
                <th>Title</th>
                <th>Outline</th>
                <th>Language</th>
                <th>Scanned On</th>
                <th>Course Info</th>
              </tr>
            </thead>
            <tbody>
              {currentRows.map((data, i) => (
                <tr key={"row-" + i}>
                  {data.status && (
                    <td style={tableCellStyles} className="text-capitalize">
                      {data.status}
                    </td>
                  )}
                  <td style={tableCellStyles}>
                    {data.hastext ? (
                      <i className={faSuccessClass} />
                    ) : (
                      <i className={faFailClass} />
                    )}
                  </td>
                  <td style={tableCellStyles}>
                    {data.hastitle ? (
                      <i className={faSuccessClass} />
                    ) : (
                      <i className={faFailClass} />
                    )}
                  </td>
                  <td style={tableCellStyles}>
                    {data.hasoutline ? (
                      <i className={faSuccessClass} />
                    ) : (
                      <i className={faFailClass} />
                    )}
                  </td>
                  <td style={tableCellStyles}>
                    {data.haslanguage ? (
                      <i className={faSuccessClass} />
                    ) : (
                      <i className={faFailClass} />
                    )}
                  </td>
                  {data.timechecked && (
                    <td style={tableCellStyles}>{data.timechecked}</td>
                  )}
                  {data.courseinfo && (
                    <td style={tableCellStyles}>
                      <p className="lead">
                        <a
                          href={`${
                            config.wwwroot
                          }/course/view.php/?id=${getCourseinfoField(
                            "courseid",
                            data.courseinfo
                          )}`}
                        >
                          {getCourseinfoField("fullname", data.courseinfo)}
                        </a>
                        <Badge variant="info" className="ml-2">
                          {getCourseinfoField("shortname", data.courseinfo)}
                        </Badge>
                      </p>
                      <p>
                        <i className="fa fa-download mr-1" aria-hidden="true" />
                        <a
                          href={`${
                            config.wwwroot
                          }/mod/resource/view.php/?id=${getCourseinfoField(
                            "instance_id",
                            data.courseinfo
                          )}`}
                          className="text-left"
                        >
                          {getCourseinfoField("filename", data.courseinfo)}
                        </a>
                      </p>
                    </td>
                  )}
                </tr>
              ))}
            </tbody>
          </Table>
        </Col>
      </Row>
    </Container>
  );
};

export default DataTable;
