import { useState } from 'react'
import api from '../api/axios'
import { useAuth } from '../context/AuthContext'
import Layout from '../components/Layout'

export default function Profile() {
  const { user, updateUser } = useAuth()
  const [form, setForm] = useState({
    name: user?.name || '',
    email: user?.email || '',
    phone: user?.phone || '',
    position: user?.position || '',
    password: '',
    password_confirmation: '',
  })
  const [errors, setErrors] = useState({})
  const [message, setMessage] = useState('')
  const [loading, setLoading] = useState(false)

  const handleChange = (e) => setForm({ ...form, [e.target.name]: e.target.value })

  const handleSubmit = async (e) => {
    e.preventDefault()
    setErrors({})
    setMessage('')
    setLoading(true)
    try {
      const formData = new FormData()
      Object.entries(form).forEach(([key, value]) => {
        if (value) formData.append(key, value)
      })
      const res = await api.post('/profile', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      updateUser(res.data.user)
      setMessage('Profile updated successfully.')
      setForm((f) => ({ ...f, password: '', password_confirmation: '' }))
    } catch (err) {
      if (err.response?.status === 422) {
        setErrors(err.response.data.errors || {})
      } else {
        setMessage(err.response?.data?.message || 'Update failed.')
      }
    } finally {
      setLoading(false)
    }
  }

  return (
    <Layout title="My Profile">
      <section className="card">
        {message && <div className="alert alert-success">{message}</div>}
        <form onSubmit={handleSubmit} className="auth-form">
          <div className="form-group">
            <label>Full Name</label>
            <input name="name" value={form.name} onChange={handleChange} required />
            {errors.name && <span className="field-error">{errors.name[0]}</span>}
          </div>
          <div className="form-group">
            <label>Email</label>
            <input name="email" type="email" value={form.email} onChange={handleChange} required />
            {errors.email && <span className="field-error">{errors.email[0]}</span>}
          </div>
          <div className="form-group">
            <label>Position</label>
            <input name="position" value={form.position} onChange={handleChange} />
          </div>
          <div className="form-group">
            <label>Phone</label>
            <input name="phone" value={form.phone} onChange={handleChange} />
          </div>
          <hr />
          <p className="form-hint">Leave blank to keep current password.</p>
          <div className="form-group">
            <label>New Password</label>
            <input name="password" type="password" value={form.password} onChange={handleChange} />
            {errors.password && <span className="field-error">{errors.password[0]}</span>}
          </div>
          <div className="form-group">
            <label>Confirm New Password</label>
            <input name="password_confirmation" type="password" value={form.password_confirmation} onChange={handleChange} />
          </div>
          <button type="submit" className="btn btn-primary" disabled={loading}>
            {loading ? 'Saving...' : 'Save Changes'}
          </button>
        </form>
      </section>
    </Layout>
  )
}
