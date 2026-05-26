# [BE-3.3] Laravel: Synchronous HTTP Client Handler & Failure Fallback Guard

When a student submits a report/curhat, the system runs an asynchronous NLP analysis process using Laravel Queue.

The `ProcessNlpAnalysisJob` sends the submitted text to an external Flask NLP service for analysis.

The NLP result is then stored in the `nlp_analyses` table containing:

* text
* response
* flag
* status
* reason
* related entity reference (`nlpable`)

If the NLP service fails or times out, the student submission process will still complete successfully and can be reviewed manually later.
