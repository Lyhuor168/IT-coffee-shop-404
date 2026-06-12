import { useEffect, useState } from 'react'
import api from '../api/axios'
import { useAuth } from '../context/AuthContext'
import Layout from '../components/Layout'

export default function Dashboard() {
  const { user } = useAuth()

  const [today, setToday] = useState(null)
  const [history, setHistory] = useState(null)
  const [leaves, setLeaves] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [actionLoading, setActionLoading] = useState(false)

  const [leaveForm, setLeaveForm] = useState({ start_date: '', end_date: '', type: 'annual', reason: '' })
  const [leaveErrors, setLeaveErrors] = useState({})
  const [leaveMessage, setLeaveMessage] = useState('')

  const loadAll = async () => {
    setError('')
    try {
      const [todayRes, historyRes, leavesRes] = await Promise.all([
        api.get('/attendance/today'),
        api.get('/attendance'),
        api.get('/leaves'),
      ])
      setToday(todayRes.data.attendance)
      setHistory(historyRes.data.attendances)
      setLeaves(leavesRes.data.leaves)
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to load dashboard.')
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    loadAll()
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [])

  const handleCheckIn = async () => {
    setActionLoading(true)
    setError('')
    try {
      const res = await api.post('/attendance/check-in')
      setToday(res.data.attendance)
    } catch (err) {
      setError(err.response?.data?.message || 'Check-in failed.')
    } finally {
      setActionLoading(false)
    }
  }

  const handleCheckOut = async () => {
    setActionLoading(true)
    setError('')
    try {
      const res = await api.post('/attendance/check-out')
      setToday(res.data.attendance)
    } catch (err) {
      setError(err.response?.data?.message || 'Check-out failed.')
    } finally {
      setActionLoading(false)
    }
  }

  const handleLeaveChange = (e) => setLeaveForm({ ...leaveForm, [e.target.name]: e.target.value })

  const handleLeaveSubmit = async (e) => {
    e.preventDefault()
    setLeaveErrors({})
    setLeaveMessage('')
    try {
      const res = await api.post('/leaves', leaveForm)
      setLeaves((prev) => ({ ...prev, data: [res.data.leave, ...prev.data] }))
      setLeaveMessage('Leave request submitted.')
      setLeaveForm({ start_date: '', end_date: '', type: 'annual', reason: '' })
    } catch (err) {
      if (err.response?.status === 422) {
        setLeaveErrors(err.response.data.errors || {})
      } else {
        setLeaveMessage(err.response?.data?.message || 'Failed to submit leave.')
      }
    }
  }

  const handleCancelLeave = async (id) => {
    try {
      await api.delete(`/leaves/${id}`)
      setLeaves((prev) => ({ ...prev, data: prev.data.filter((l) => l.id !== id) }))
    } catch (err) {
      setLeaveMessage(err.response?.data?.message || 'Failed to cancel.')
    }
  }

  return (
    <Layout title="Dashboard">
      {error && <div className="alert alert-error">{error}</div>}
      {loading ? (
        <p>Loading...</p>
      ) : (
        <>
          <section className="card">
            <h2>Today — {new Date().toLocaleDateString()}</h2>
            <p>Welcome, {user?.name}</p>

            {today?.check_in && (
              <p><strong>Checked in:</strong> {new Date(today.check_in).toLocaleTimeString()} ({today.status})</p>
            )}
            {today?.check_out && (
              <p><strong>Checked out:</strong> {new Date(today.check_out).toLocaleTimeString()}</p>
            )}

            <div className="action-buttons">
              {!today?.check_in && (
                <button className="btn btn-primary" onClick={handleCheckIn} disabled={actionLoading}>
                  {actionLoading ? 'Processing...' : 'Check In'}
                </button>
              )}
              {today?.check_in && !today?.check_out && (
                <button className="btn btn-secondary" onClick={handleCheckOut} disabled={actionLoading}>
                  {actionLoading ? 'Processing...' : 'Check Out'}
                </button>
              )}
              {today?.check_in && today?.check_out && (
                <p className="muted">You're done for today. See you tomorrow!</p>
              )}
            </div>
          </section>

          <section className="card">
            <h2>Attendance History</h2>
            <table className="data-table">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Check In</th>
                  <th>Check Out</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                {history?.data?.map((a) => (
                  <tr key={a.id}>
                    <td>{new Date(a.date).toLocaleDateString()}</td>
                    <td>{a.check_in ? new Date(a.check_in).toLocaleTimeString() : '-'}</td>
                    <td>{a.check_out ? new Date(a.check_out).toLocaleTimeString() : '-'}</td>
                    <td><span className={`badge badge-${a.status}`}>{a.status}</span></td>
                  </tr>
                ))}
                {history?.data?.length === 0 && (
                  <tr><td colSpan="4" className="muted">No attendance records yet.</td></tr>
                )}
              </tbody>
            </table>
          </section>

          <section className="card">
            <h2>Request Leave</h2>
            {leaveMessage && <div className="alert alert-success">{leaveMessage}</div>}
            <form onSubmit={handleLeaveSubmit} className="auth-form leave-form">
              <div className="form-row">
                <div className="form-group">
                  <label>Start Date</label>
                  <input type="date" name="start_date" value={leaveForm.start_date} onChange={handleLeaveChange} required />
                  {leaveErrors.start_date && <span className="field-error">{leaveErrors.start_date[0]}</span>}
                </div>
                <div className="form-group">
                  <label>End Date</label>
                  <input type="date" name="end_date" value={leaveForm.end_date} onChange={handleLeaveChange} required />
                  {leaveErrors.end_date && <span className="field-error">{leaveErrors.end_date[0]}</span>}
                </div>
                <div className="form-group">
                  <label>Type</label>
                  <select name="type" value={leaveForm.type} onChange={handleLeaveChange}>
                    <option value="annual">Annual</option>
                    <option value="sick">Sick</option>
                    <option value="unpaid">Unpaid</option>
                    <option value="other">Other</option>
                  </select>
                </div>
              </div>
              <div className="form-group">
                <label>Reason</label>
                <textarea name="reason" rows="3" value={leaveForm.reason} onChange={handleLeaveChange} required />
                {leaveErrors.reason && <span className="field-error">{leaveErrors.reason[0]}</span>}
              </div>
              <button type="submit" className="btn btn-primary">Submit Request</button>
            </form>
          </section>

          <section className="card">
            <h2>My Leave Requests</h2>
            <table className="data-table">
              <thead>
                <tr>
                  <th>Period</th>
                  <th>Type</th>
                  <th>Reason</th>
                  <th>Status</th>
                  <th>Remark</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                {leaves?.data?.map((l) => (
                  <tr key={l.id}>
                    <td>{new Date(l.start_date).toLocaleDateString()} – {new Date(l.end_date).toLocaleDateString()}</td>
                    <td>{l.type}</td>
                    <td>{l.reason}</td>
                    <td><span className={`badge badge-leave-${l.status}`}>{l.status}</span></td>
                    <td>{l.admin_remark || '-'}</td>
                    <td>
                      {l.status === 'pending' && (
                        <button className="btn btn-danger btn-sm" onClick={() => handleCancelLeave(l.id)}>Cancel</button>
                      )}
                    </td>
                  </tr>
                ))}
                {leaves?.data?.length === 0 && (
                  <tr><td colSpan="6" className="muted">No leave requests yet.</td></tr>
                )}
              </tbody>
            </table>
          </section>
        </>
      )}
    </Layout>
  )
}
