import React, {useState} from "react"
import createRoot from "react-dom/client"
import {
    TextField,
    Stack,
    Button,
    Alert
} from "@mui/material"

const RegStepOne = (props) => {
    const [data, _setData] = useState(props.data)
    const [errors, setErrors] = useState({})
    const [fail, setFail] = useState(false)
    const [processing, setProcessing] = useState(false)

    const updateData = (e) => {
        let _data = data
        _data[e.target.name] = e.target.value
        _setData({..._data})
    }

    const submit = () => {
        setProcessing(true)
        let fd = new FormData()
        fd.append('action', 'jg_validate_user_data');
        fd.append('csrf', props.server.csrf)
        for( const [key, value] of Object.entries(data) ) {
            fd.append(key, value)
        }

        fetch( props.server.url, {
            method: 'POST',
            body: fd
        })
        .then( res => res.json() )
        .then( res => {
            switch( res.result ) {
                case 'error':
                    setErrors(res.errors)
                    setFail(false)
                    break
                case 'success':
                    setErrors({})
                    setFail(false)
                    props.nextStep(data)
                    break
                case 'fail':
                    setFail(res.error)
                    break
            }
        })
        .catch(e => setFail(e))
        .finally(() => setProcessing(false))
    }

    return(
        <Stack spacing={2}>
            {fail ? <Alert severity="error">{fail}</Alert> : <></>}
            <TextField
                id="user_email"
                label="Email"
                variant="outlined"
                name="email"
                required
                value={data.email}
                onChange={updateData}
                error={errors.email ? true : false}
                helperText={errors.email}
                />
            {/* <TextField
                id="password"
                type="password"
                label="Password"
                variant="outlined"
                name="password"
                required
                value={data.password}
                onChange={updateData}
                error={errors.password ? true : false}
                helperText={errors.password}
                />
            <TextField
                id="password_confirmed"
                type="password"
                label="Repeat Password"
                variant="outlined"
                name="password_confirmed"
                required
                value={data.password_confirmed}
                onChange={updateData}
                error={errors.password_confirmed ? true : false}
                helperText={errors.password_confirmed}
                /> */}
            <Stack sx={{width:'100%'}} direction='row' justifyContent='end'>
                <Button
                    variant="contained"
                    onClick={submit}
                    >
                        Next
                </Button>
            </Stack>
        </Stack>
    )
}

export default RegStepOne