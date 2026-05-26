# [BE-4.1] Implement False Positive Data State Mutator & SLA Interceptor Hook

## Overview

BK teachers can mark an NLP response as **false positive** when reviewing a student report (curhat).
This action is only allowed when the report status is **"menunggu" (waiting)**.

---

## Business Rules

### 1. Allowed Condition

A report can only be marked as false positive if:

* `status = menunggu`

---

### 2. Effects of False Positive

When a report is marked as false positive:

* `priority` is set to `rendah`
* `cutdown_for_report` is cleared (`null`)
* NLP status is updated to `false_positive`
* NLP `reason` is stored (as justification from BK)
* Any escalation logic is canceled

---

### 3. Status Flow (Curhat Lifecycle)

```text id="k2p9aa"
menunggu → dijadwalkan → ditanggapi → selesai
```

---

## NLP Metadata

Each false positive action must include a **reason**, stored in NLP analysis:

* Used to explain why the NLP prediction is incorrect
* Helps improve future model training
* Stored as dataset feedback signal

Example fields:

* `status = false_positive`
* `reason = "misclassified emotional tone / not relevant / normal conversation"`

---

## Scope of Impact

All reports with:

* priority `sedang`
* priority `tinggi`

will be downgraded to:

* `rendah`

when marked as false positive.

---

## Summary

False positive marking:

* Only allowed in `menunggu` status
* Downgrades priority to `rendah`
* Removes SLA countdown (`cutdown_for_report`)
* Stores NLP correction reason for model improvement
* Prevents escalation or alerts
* Keeps reporting workflow consistent
