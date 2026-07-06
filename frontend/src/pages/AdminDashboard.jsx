import { useEffect, useState, useCallback } from 'react'
import api from '../api/axios'
import Layout from '../components/Layout'

export default function AdminDashboard() {
  const [tab, setTab] = useState('leaves')

  const [leaves, setLeaves] = useState(null)
  const [attendances, setAttendances] = useState(null)
  const [users, setUsers] = useState(null)
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(true)

  const loadData = useCallback(async () => {
    try {
      if (tab === 'leaves') {
        const res = await api.get('/admin/leaves')
        setLeaves(res.data.leaves)
      } else if (tab === 'attendances') {
        const res = await api.get('/admin/attendances')
        setAttendances(res.data.attendances)
      } else if (tab === 'users') {
        const res = await api.get('/admin/users')
        setUsers(res.data.users)
      }
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to load data.')
    } finally {
      setLoading(false)
    }
  }, [tab])

  useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect
    loadData()
  }, [loadData])

  const handleReview = async (id, status) => {
    const remark = window.prompt(`Remark for ${status} (optional):`, '')
    try {
      const res = await api.patch(`/admin/leaves/${id}/review`, { status, admin_remark: remark || null })
      setLeaves((prev) => ({
        ...prev,
        data: prev.data.map((l) => (l.id === id ? res.data.leave : l)),
      }))
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to review leave.')
    }
  }

  const handleRoleChange = async (userId, role) => {
    try {
      const res = await api.patch(`/admin/users/${userId}/role`, { role })
      setUsers((prev) => ({
        ...prev,
        data: prev.data.map((u) => (u.id === userId ? res.data.user : u)),
      }))
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to update role.')
    }
  }

  const handleDeleteUser = async (userId) => {
    if (!window.confirm('Delete this user? This cannot be undone.')) return
    try {
      await api.delete(`/admin/users/${userId}`)
      setUsers((prev) => ({ ...prev, data: prev.data.filter((u) => u.id !== userId) }))
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to delete user.')
    }
  }

  const changeTab = (t) => {
    setTab(t)
    setError('')
    setLoading(true)
  }

  return (
    <Layout title="Admin Dashboard">
      <div className="tabs">
        <button className={`tab ${tab === 'leaves' ? 'tab-active' : ''}`} onClick={() => changeTab('leaves')}>Leave Requests</button>
        <button className={`tab ${tab === 'attendances' ? 'tab-active' : ''}`} onClick={() => changeTab('attendances')}>Attendance</button>
        <button className={`tab ${tab === 'users' ? 'tab-active' : ''}`} onClick={() => changeTab('users')}>Users</button>
      </div>

      {error && <div className="alert alert-error">{error}</div>}

      {loading ? (
        <p>Loading...</p>
      ) : (
        <section className="card">
          {tab === 'leaves' && (
            <table className="data-table">
              <thead>
                <tr>
                  <th>Employee</th>
                  <th>Period</th>
                  <th>Type</th>
                  <th>Reason</th>
                  <th>Status</th>
                  <th>Reviewer</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                {leaves?.data?.map((l) => (
                  <tr key={l.id}>
                    <td>{l.user?.name}<br /><span className="muted">{l.user?.position}</span></td>
                    <td>{new Date(l.start_date).toLocaleDateString()} – {new Date(l.end_date).toLocaleDateString()}</td>
                    <td>{l.type}</td>
                    <td>{l.reason}</td>
                    <td><span className={`badge badge-leave-${l.status}`}>{l.status}</span></td>
                    <td>{l.reviewer?.name || '-'}</td>
                    <td>
                      {l.status === 'pending' ? (
                        <div className="action-buttons">
                          <button className="btn btn-primary btn-sm" onClick={() => handleReview(l.id, 'approved')}>Approve</button>
                          <button className="btn btn-danger btn-sm" onClick={() => handleReview(l.id, 'rejected')}>Reject</button>
                        </div>
                      ) : (
                        <span className="muted">{l.admin_remark || '-'}</span>
                      )}
                    </td>
                  </tr>
                ))}
                {leaves?.data?.length === 0 && (
                  <tr><td colSpan="7" className="muted">No leave requests.</td></tr>
                )}
              </tbody>
            </table>
          )}

          {tab === 'attendances' && (
            <table className="data-table">
              <thead>
                <tr>
                  <th>Employee</th>
                  <th>Date</th>
                  <th>Check In</th>
                  <th>Check Out</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                {attendances?.data?.map((a) => (
                  <tr key={a.id}>
                    <td>{a.user?.name}</td>
                    <td>{new Date(a.date).toLocaleDateString()}</td>
                    <td>{a.check_in ? new Date(a.check_in).toLocaleTimeString() : '-'}</td>
                    <td>{a.check_out ? new Date(a.check_out).toLocaleTimeString() : '-'}</td>
                    <td><span className={`badge badge-${a.status}`}>{a.status}</span></td>
                  </tr>
                ))}
                {attendances?.data?.length === 0 && (
                  <tr><td colSpan="5" className="muted">No attendance records.</td></tr>
                )}
              </tbody>
            </table>
          )}

          {tab === 'users' && (
            <table className="data-table">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Position</th>
                  <th>Role</th>
                  <th>Joined</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                {users?.data?.map((u) => (
                  <tr key={u.id}>
                    <td>{u.name}</td>
                    <td>{u.email}</td>
                    <td>{u.position || '-'}</td>
                    <td>
                      <select value={u.role} onChange={(e) => handleRoleChange(u.id, e.target.value)}>
                        <option value="employee">employee</option>
                        <option value="admin">admin</option>
                      </select>
                    </td>
                    <td>{new Date(u.created_at).toLocaleDateString()}</td>
                    <td>
                      <button className="btn btn-danger btn-sm" onClick={() => handleDeleteUser(u.id)}>Delete</button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </section>
      )}
    </Layout>
  )
}
