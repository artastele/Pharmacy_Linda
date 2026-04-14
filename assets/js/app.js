const showMessage = (selector, message, isError = false) => {
    const el = document.querySelector(selector);
    if (!el) return;
    el.textContent = message;
    el.className = isError ? 'message error' : 'message';
};

const fetchJson = async (url, options = {}) => {
    const res = await fetch(url, options);
    const data = await res.json();
    if (!res.ok) {
        throw new Error(data.message || 'Request failed');
    }
    return data;
};

const loginForm = document.querySelector('#login-form');
if (loginForm) {
    loginForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = new FormData(loginForm);
        try {
            const data = await fetchJson('api/auth.php?action=login', { method: 'POST', body: form });
            const role = data.role;
            if (role === 'Intern') window.location.href = 'dashboard_intern.php';
            if (role === 'HR Personnel') window.location.href = 'dashboard_hr.php';
            if (role === 'Pharmacist') window.location.href = 'dashboard_pharmacist.php';
        } catch (error) {
            showMessage('#login-message', error.message, true);
        }
    });
}

const registerForm = document.querySelector('#register-form');
if (registerForm) {
    registerForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = new FormData(registerForm);
        try {
            await fetchJson('api/auth.php?action=register', { method: 'POST', body: form });
            showMessage('#register-message', 'Registration successful. Redirecting...');
            setTimeout(() => window.location.href = 'choose_role.php', 900);
        } catch (error) {
            showMessage('#register-message', error.message, true);
        }
    });
}

const googleBtn = document.querySelector('#google-login-btn');
if (googleBtn) {
    googleBtn.addEventListener('click', () => {
        window.location.href = 'google_login.php';
    });
}

const pageRole = window.pageData?.role;

const statusBadge = (status) => {
    const lower = status.toLowerCase();
    let cls = 'badge pending';
    if (status === 'Approved' || status === 'Complete') cls = 'badge complete';
    if (status === 'Rejected' || status === 'Missing' || status === 'Incomplete') cls = 'badge rejected';
    if (status === 'Pending') cls = 'badge pending';
    return `<span class="${cls}">${status}</span>`;
};

const loadInternDashboard = async () => {
    const response = await fetchJson('api/submissions.php?action=list_user');
    const items = response.items;
    const total = items.length;
    let uploaded = 0;
    let approved = 0;
    let missing = 0;
    const rows = items.map(item => {
        const status = item.status || 'Missing';
        if (item.filename) uploaded++;
        if (status === 'Approved') approved++;
        if (status === 'Missing') missing++;
        return `<tr>
            <td>${item.title}</td>
            <td>${item.description}</td>
            <td>${item.filename ? `<a href="uploads/${item.filename}" target="_blank">View</a>` : 'No file'}</td>
            <td>${statusBadge(status)}</td>
            <td>${item.remarks || '—'}</td>
            <td>${item.uploaded_at ? new Date(item.uploaded_at).toLocaleDateString() : '—'}</td>
            <td>${item.filename ? `<form class="upload-form" data-id="${item.requirement_id}"><input type="file" name="document" required /><button type="submit" class="btn btn-primary">Replace</button></form>` : `<form class="upload-form" data-id="${item.requirement_id}"><input type="file" name="document" required /><button type="submit" class="btn btn-primary">Upload</button></form>`}</td>
        </tr>`;
    }).join('');

    document.querySelector('#stat-total').textContent = total;
    document.querySelector('#stat-uploaded').textContent = uploaded;
    document.querySelector('#stat-approved').textContent = approved;
    document.querySelector('#stat-missing').textContent = missing;
    const completePercent = total ? Math.round((approved / total) * 100) : 0;
    document.querySelector('#progress-bar').style.width = `${completePercent}%`;
    document.querySelector('#progress-text').textContent = `${completePercent}% complete`;
    document.querySelector('#requirements-list').innerHTML = `
        <table><thead><tr><th>Requirement</th><th>Description</th><th>File</th><th>Status</th><th>Remark</th><th>Uploaded</th><th>Action</th></tr></thead><tbody>${rows}</tbody></table>`;
    document.querySelector('#checklist-table').innerHTML = document.querySelector('#requirements-list').innerHTML;
    document.querySelectorAll('.upload-form').forEach(form => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const id = form.dataset.id;
            const file = form.querySelector('input[type=file]').files[0];
            if (!file) return;
            const payload = new FormData();
            payload.append('requirement_id', id);
            payload.append('document', file);
            try {
                await fetchJson('api/submissions.php?action=upload', { method: 'POST', body: payload });
                await loadInternDashboard();
            } catch (err) {
                alert(err.message);
            }
        });
    });
    const scheduleResponse = await fetchJson('api/interviews.php?action=intern_schedule');
    const schedule = scheduleResponse.schedule;
    document.querySelector('#interview-schedule').innerHTML = schedule ? `
        <div class="notification-card">
            <p><strong>Interview Date:</strong> ${new Date(schedule.interview_date).toLocaleString()}</p>
            <p><strong>Mode:</strong> ${schedule.interview_mode}</p>
            <p><strong>${schedule.interview_mode === 'Online' ? 'Meeting Link' : 'Location'}:</strong> ${schedule.interview_mode === 'Online' ? (schedule.interview_link ? `<a href="${schedule.interview_link}" target="_blank">${schedule.interview_link}</a>` : 'Not provided') : (schedule.interview_location || 'Not provided')}</p>
            <p><strong>Status:</strong> ${schedule.status}</p>
            <p><strong>Message:</strong> ${schedule.notification_message || 'No additional message.'}</p>
        </div>
    ` : '<div class="notification-card"><p>No interview schedule has been assigned yet. Please wait for HR to schedule your interview.</p></div>';
    const policyResponse = await fetchJson('api/policies.php?action=list');
    document.querySelector('#policies-list').innerHTML = policyResponse.policies.map((policy, index) => `
        <div class="policy-title-box">
            <div class="policy-title" data-id="${policy.id}"><strong>${index + 1}. ${policy.title}</strong></div>
        </div>
        <div class="policy-content-box" id="content-${policy.id}" style="display:none;">
            ${policy.content}
        </div>
    `).join('');
    document.querySelectorAll('.policy-title').forEach(title => {
        title.addEventListener('click', () => {
            const contentId = 'content-' + title.dataset.id;
            const content = document.getElementById(contentId);
            content.style.display = content.style.display === 'none' ? 'block' : 'none';
        });
    });
    try {
        const myScheduleResponse = await fetchJson('api/schedules.php?action=get&intern_id=' + window.pageData.userId);
        const mySchedule = myScheduleResponse.schedule;
        document.querySelector('#my-schedule').innerHTML = mySchedule ? `
            <table class="schedule-table">
                <thead><tr><th>Day</th><th>Hours</th></tr></thead>
                <tbody>
                    <tr><td>Monday</td><td>${mySchedule.monday}</td></tr>
                    <tr><td>Tuesday</td><td>${mySchedule.tuesday}</td></tr>
                    <tr><td>Wednesday</td><td>${mySchedule.wednesday}</td></tr>
                    <tr><td>Thursday</td><td>${mySchedule.thursday}</td></tr>
                    <tr><td>Friday</td><td>${mySchedule.friday}</td></tr>
                    <tr><td>Saturday</td><td>${mySchedule.saturday}</td></tr>
                    <tr><td>Sunday</td><td>${mySchedule.sunday}</td></tr>
                </tbody>
            </table>
            <p><strong>Total Hours:</strong> ${mySchedule.total_hours}</p>
            <p><strong>Notes:</strong> ${mySchedule.notes || 'None'}</p>
        ` : '<p>No schedule assigned yet.</p>';
    } catch (err) {
        console.error('Error loading schedule:', err);
        document.querySelector('#my-schedule').innerHTML = '<p>Unable to load schedule. Please try refreshing the page.</p>';
    }
};

const loadHRDashboard = async () => {
    const summary = await fetchJson('api/submissions.php?action=summary');
    document.querySelector('#hr-total-interns').textContent = summary.total_interns;
    document.querySelector('#hr-pending').textContent = summary.pending_submissions;
    document.querySelector('#hr-completed').textContent = summary.completed_interns;
    const requirements = await fetchJson('api/requirements.php?action=list');
    const requirementRows = requirements.requirements.map(item => `
        <tr>
            <td>${item.title}</td>
            <td>${item.description}</td>
            <td><button type="button" class="action-btn edit-requirement" data-id="${item.id}" data-title="${item.title}" data-description="${item.description}">Edit</button> | <button type="button" class="action-btn delete-requirement" data-id="${item.id}">Delete</button></td>
        </tr>
    `).join('');
    document.querySelector('#requirements-admin-list').innerHTML = `<table><thead><tr><th>Requirement</th><th>Description</th><th>Actions</th></tr></thead><tbody>${requirementRows}</tbody></table>`;
    document.querySelectorAll('.edit-requirement').forEach(button => {
        button.addEventListener('click', () => {
            const form = document.querySelector('#requirement-form');
            form.id.value = button.dataset.id;
            form.title.value = button.dataset.title;
            form.description.value = button.dataset.description;
        });
    });
    document.querySelectorAll('.delete-requirement').forEach(button => {
        button.addEventListener('click', async () => {
            if (!confirm('Delete this requirement?')) return;
            const form = new FormData();
            form.append('id', button.dataset.id);
            await fetchJson('api/requirements.php?action=delete', { method: 'POST', body: form });
            await loadHRDashboard();
        });
    });

    const policies = await fetchJson('api/policies.php?action=list');
    const policyRows = policies.policies.map(item => `
        <tr>
            <td>${item.title}</td>
            <td>${item.content}</td>
            <td><button type="button" class="action-btn edit-policy" data-id="${item.id}" data-title="${item.title}" data-content="${item.content}">Edit</button> | <button type="button" class="action-btn delete-policy" data-id="${item.id}">Delete</button></td>
        </tr>
    `).join('');
    document.querySelector('#policies-admin-list').innerHTML = `<table><thead><tr><th>Title</th><th>Content</th><th>Actions</th></tr></thead><tbody>${policyRows}</tbody></table>`;
    document.querySelectorAll('.edit-policy').forEach(button => {
        button.addEventListener('click', () => {
            const form = document.querySelector('#policy-form');
            form.id.value = button.dataset.id;
            form.title.value = button.dataset.title;
            form.content.value = button.dataset.content;
        });
    });
    document.querySelectorAll('.delete-policy').forEach(button => {
        button.addEventListener('click', async () => {
            if (!confirm('Delete this policy?')) return;
            const form = new FormData();
            form.append('id', button.dataset.id);
            await fetchJson('api/policies.php?action=delete', { method: 'POST', body: form });
            await loadHRDashboard();
        });
    });

    const submissions = await fetchJson('api/submissions.php?action=list_all');
    document.querySelector('#submission-review-table').innerHTML = `<table><thead><tr><th>Intern</th><th>Requirement</th><th>File</th><th>Status</th><th>Remarks</th><th>Review</th></tr></thead><tbody>${submissions.items.map(item => `
            <tr>
                <td>${item.full_name} <div class="small-note">${item.email}</div></td>
                <td>${item.title}</td>
                <td>${item.filename ? `<a href="uploads/${item.filename}" target="_blank">View</a>` : 'No file'}</td>
                <td>${statusBadge(item.status)}</td>
                <td>${item.remarks || '—'}</td>
                <td>
                    <form class="review-form" data-id="${item.id}">
                        <select name="status"><option value="Approved">Approved</option><option value="Rejected">Rejected</option><option value="Pending">Pending</option></select>
                        <input type="text" name="remarks" placeholder="Remark" />
                        <button class="btn btn-primary">Save</button>
                    </form>
                </td>
            </tr>`).join('')}</tbody></table>`;
    document.querySelectorAll('.review-form').forEach(form => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const id = form.dataset.id;
            const reviewData = new FormData(form);
            reviewData.append('id', id);
            await fetchJson('api/submissions.php?action=review', { method: 'POST', body: reviewData });
            await loadHRDashboard();
        });
    });
    // Load applicant approval
    const approvalResponse = await fetchJson('api/applicants.php?action=list_pending_approval');
    document.querySelector('#applicant-approval-table').innerHTML = `<table><thead><tr><th>Name</th><th>Email</th><th>Action</th></tr></thead><tbody>${approvalResponse.applicants.map(app => `
            <tr>
                <td>${app.full_name}</td>
                <td>${app.email}</td>
                <td><button class="action-btn approve-app" data-id="${app.id}">Approve for Interview</button></td>
            </tr>`).join('')}</tbody></table>`;
    document.querySelectorAll('.approve-app').forEach(btn => {
        btn.addEventListener('click', async () => {
            await fetchJson('api/applicants.php?action=approve&id=' + btn.dataset.id, { method: 'POST' });
            await loadHRDashboard();
        });
    });
};

const loadPharmacistDashboard = async () => {
    const response = await fetchJson('api/submissions.php?action=intern_report');
    document.querySelector('#intern-monitoring-list').innerHTML = `<table><thead><tr><th>Intern</th><th>Status</th><th>Approved Docs</th><th>Completion</th></tr></thead><tbody>${response.interns.map(item => `
            <tr>
                <td>${item.full_name}<div class="small-note">${item.email}</div></td>
                <td>${statusBadge(item.status)}</td>
                <td>${item.approved}</td>
                <td>${item.percentage}%</td>
            </tr>`).join('')}</tbody></table>`;
    document.querySelector('#intern-report-table').innerHTML = `<table><thead><tr><th>Intern</th><th>Completion Percentage</th><th>Status</th></tr></thead><tbody>${response.interns.map(item => `
            <tr>
                <td>${item.full_name}</td>
                <td>${item.percentage}%</td>
                <td>${statusBadge(item.status)}</td>
            </tr>`).join('')}</tbody></table>`;
};

if (pageRole === 'Intern') {
    loadInternDashboard().catch(console.error);
}
if (pageRole === 'HR Personnel') {
    loadHRDashboard().catch(console.error);
    const reqForm = document.querySelector('#requirement-form');
    if (reqForm) {
        reqForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const payload = new FormData(reqForm);
            const action = payload.get('id') ? 'update' : 'create';
            await fetchJson(`api/requirements.php?action=${action}`, { method: 'POST', body: payload });
            reqForm.reset();
            await loadHRDashboard();
        });
    }
    const policyForm = document.querySelector('#policy-form');
    if (policyForm) {
        policyForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const payload = new FormData(policyForm);
            const action = payload.get('id') ? 'update' : 'create';
            await fetchJson(`api/policies.php?action=${action}`, { method: 'POST', body: payload });
            policyForm.reset();
            await loadHRDashboard();
        });
    }
}
if (pageRole === 'Pharmacist') {
    loadPharmacistDashboard().catch(console.error);
}
