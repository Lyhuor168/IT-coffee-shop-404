import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'

export default function Register() {
  const { register } = useAuth()
  const navigate = useNavigate()
  const [form, setForm] = useState({
    name: '', email: '', password: '', password_confirmation: '', phone: '', position: '',
  })
  const [errors, setErrors] = useState({})
  const [generalError, setGeneralError] = useState('')
  const [loading, setLoading] = useState(false)

  const handleChange = (e) => setForm({ ...form, [e.target.name]: e.target.value })

  const handleSubmit = async (e) => {
    e.preventDefault()
    setErrors({})
    setGeneralError('')
    setLoading(true)
    try {
      await register(form)
      navigate('/dashboard')
    } catch (err) {
      if (err.response?.status === 422) {
        setErrors(err.response.data.errors || {})
        setGeneralError(err.response.data.message)
      } else {
        setGeneralError(err.response?.data?.message || 'Registration failed.')
      }
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="auth-page">
      <div className="auth-card">
        <h1>Create Account</h1>
        {generalError && <div className="alert alert-error">{generalError}</div>}
        <form onSubmit={handleSubmit} className="auth-form">
          <div className="form-group">
            <label htmlFor="name">Full Name</label>
            <input id="name" name="name" required value={form.name} onChange={handleChange} />
            {errors.name && <span className="field-error">{errors.name[0]}</span>}
          </div>
          <div className="form-group">
            <label htmlFor="email">Email</label>
            <input id="email" name="email" type="email" required value={form.email} onChange={handleChange} />
            {errors.email && <span className="field-error">{errors.email[0]}</span>}
          </div>
          <div className="form-group">
            <label htmlFor="position">Position</label>
            <input id="position" name="position" value={form.position} onChange={handleChange} placeholder="e.g. Software Developer" />
          </div>
          <div className="form-group">
            <label htmlFor="phone">Phone</label>
            <input id="phone" name="phone" value={form.phone} onChange={handleChange} />
          </div>
          <div className="form-group">
            <label htmlFor="password">Password</label>
            <input id="password" name="password" type="password" required value={form.password} onChange={handleChange} />
            {errors.password && <span className="field-error">{errors.password[0]}</span>}
          </div>
          <div className="form-group">
            <label htmlFor="password_confirmation">Confirm Password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required value={form.password_confirmation} onChange={handleChange} />
          </div>
          <button type="submit" className="btn btn-primary" disabled={loading}>
            {loading ? 'Creating account...' : 'Register'}
          </button>
        </form>
        <p className="auth-footer">
          Already have an account? <Link to="/login">Sign In</Link>
        </p>
      </div>
    </div>
  )
}
